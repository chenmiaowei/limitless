<?php

namespace orangins\modules\auth\actions;

use AphrontWriteGuard;
use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\events\AuthWillRegisterUserEvent;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\models\PhabricatorRegistrationProfile;
use orangins\modules\auth\password\PhabricatorAuthPasswordException;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\auth\view\PhabricatorAuthAccountView;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\metamta\view\PhabricatorMetaMTAMailBody;
use PhutilOpaqueEnvelope;
use orangins\lib\events\constant\PhabricatorEventType;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use AphrontDuplicateKeyQueryException;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormRecaptchaControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIInvisibleCharacterView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorAuthRegisterAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthRegisterAction extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return AphrontResponse|\orangins\lib\view\AphrontDialogView|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws AphrontDuplicateKeyQueryException
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $account_key = $request->getURIData('akey');

        if ($viewer->isLoggedIn()) {
            return (new AphrontRedirectResponse())->setURI($this->getHomeUrl());
        }

        /** @var PhabricatorAuthProvider $provider */
        $provider = null;
        /** @var PhabricatorExternalAccount $account */
        $account = null;
        $is_setup = false;
        if (strlen($account_key)) {
            $result = $this->loadAccountForRegistrationOrLinking($account_key);
            list($account, $provider, $response) = $result;
            $is_default = false;
        } else if ($this->isFirstTimeSetup()) {
            list($account, $provider, $response) = $this->loadSetupAccount();
            $is_default = true;
            $is_setup = true;
        } else {
            list($account, $provider, $response) = $this->loadDefaultAccount();
            $is_default = true;
        }

        if ($response) {
            return $response;
        }

        $invite = $this->loadInvite();

        if (!$provider->shouldAllowRegistration()) {
            if ($invite) {
                // If the user has an invite, we allow them to register with any
                // provider, even a login-only provider.
            } else {
                // TODO: This is a routine error if you click "Login" on an external
                // auth source which doesn't allow registration. The error should be
                // more tailored.

                return $this->renderError(
                    \Yii::t("app",
                        'The account you are attempting to register with uses an ' .
                        'authentication provider ("{0}") which does not allow ' .
                        'registration. An administrator may have recently disabled ' .
                        'registration with this provider.', [
                            $provider->getProviderName()
                        ]));
            }
        }

        $errors = array();

        $user = new PhabricatorUser();

        $default_username = $account->getUsername();
        $default_realname = $account->getRealName();

        $account_type = PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT;
        $content_source = PhabricatorContentSource::newFromRequest($request);

        $default_email = $account->getEmail();

        if ($invite) {
            $default_email = $invite->getEmailAddress();
        }

        if ($default_email !== null) {
            if (!PhabricatorUserEmail::isValidAddress($default_email)) {
                $errors[] = \Yii::t("app",
                    'The email address associated with this external account ("%s") is ' .
                    'not a valid email address and can not be used to register a ' .
                    'Phabricator account. Choose a different, valid address.',
                    JavelinHtml::phutil_tag('strong', array(), $default_email));
                $default_email = null;
            }
        }

        if ($default_email !== null) {
            // We should bypass policy here because e.g. limiting an application use
            // to a subset of users should not allow the others to overwrite
            // configured application emails.
            $application_email = PhabricatorMetaMTAApplicationEmail::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withAddresses(array($default_email))
                ->executeOne();
            if ($application_email) {
                $errors[] = \Yii::t("app",
                    'The email address associated with this account ("%s") is ' .
                    'already in use by an application and can not be used to ' .
                    'register a new Phabricator account. Choose a different, valid ' .
                    'address.',
                    JavelinHtml::phutil_tag('strong', array(), $default_email));
                $default_email = null;
            }
        }

        $show_existing = null;
        if ($default_email !== null) {
            // If the account source provided an email, but it's not allowed by
            // the configuration, roadblock the user. Previously, we let the user
            // pick a valid email address instead, but this does not align well with
            // user expectation and it's not clear the cases it enables are valuable.
            // See discussion in T3472.
            if (!PhabricatorUserEmail::isAllowedAddress($default_email)) {
                $debug_email = new PHUIInvisibleCharacterView($default_email);
                return $this->renderError(
                    array(
                        \Yii::t("app",
                            'The account you are attempting to register with has an invalid ' .
                            'email address (%s). This Phabricator install only allows ' .
                            'registration with specific email addresses:',
                            $debug_email),
                        JavelinHtml::phutil_tag('br'),
                        JavelinHtml::phutil_tag('br'),
                        PhabricatorUserEmail::describeAllowedAddresses(),
                    ));
            }

            // If the account source provided an email, but another account already
            // has that email, just pretend we didn't get an email.
            if ($default_email !== null) {
                $same_email = PhabricatorUserEmail::find()->andWhere(['address' => $default_email])->one();
                if ($same_email) {
                    if ($invite) {
                        // We're allowing this to continue. The fact that we loaded the
                        // invite means that the address is nonprimary and unverified and
                        // we're OK to steal it.
                    } else {
                        $show_existing = $default_email;
                        $default_email = null;
                    }
                }
            }
        }

        if ($show_existing !== null) {
            if (!$request->getInt('phase')) {
                return $this->newDialog()
                    ->setTitle(\Yii::t("app", 'Email Address Already in Use'))
                    ->addHiddenInput('phase', 1)
                    ->appendParagraph(
                        \Yii::t("app",
                            'You are creating a new Phabricator account linked to an ' .
                            'existing external account from outside Phabricator.'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'The email address ("%s") associated with the external account ' .
                            'is already in use by an existing Phabricator account. Multiple ' .
                            'Phabricator accounts may not have the same email address, so ' .
                            'you can not use this email address to register a new ' .
                            'Phabricator account.',
                            JavelinHtml::phutil_tag('strong', array(), $show_existing)))
                    ->appendParagraph(
                        \Yii::t("app",
                            'If you want to register a new account, continue with this ' .
                            'registration workflow and choose a new, unique email address ' .
                            'for the new account.'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'If you want to link an existing Phabricator account to this ' .
                            'external account, do not continue. Instead: log in to your ' .
                            'existing account, then go to "Settings" and link the account ' .
                            'in the "External Accounts" panel.'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'If you continue, you will create a new account. You will not ' .
                            'be able to link this external account to an existing account.'))
                    ->addCancelButton('/auth/login/', \Yii::t("app", 'Cancel'))
                    ->addSubmitButton(\Yii::t("app", 'Create New Account'));
            } else {
                $errors[] = \Yii::t("app",
                    'The external account you are registering with has an email address ' .
                    'that is already in use ("%s") by an existing Phabricator account. ' .
                    'Choose a new, valid email address to register a new Phabricator ' .
                    'account.',
                    JavelinHtml::phutil_tag('strong', array(), $show_existing));
            }
        }

        $profile = (new PhabricatorRegistrationProfile())
            ->setDefaultUsername($default_username)
            ->setDefaultEmail($default_email)
            ->setDefaultRealName($default_realname)
            ->setCanEditUsername(true)
            ->setCanEditEmail(($default_email === null))
            ->setCanEditRealName(true)
            ->setShouldVerifyEmail(false);


        $renderActionListEvent = new AuthWillRegisterUserEvent();
        $renderActionListEvent
            ->setAccount($account)
            ->setProfile($profile);
        Yii::$app->trigger(PhabricatorEventType::TYPE_AUTH_WILLREGISTERUSER, $renderActionListEvent);


        $default_username = $profile->getDefaultUsername();
        $default_email = $profile->getDefaultEmail();
        $default_realname = $profile->getDefaultRealName();

        $can_edit_username = $profile->getCanEditUsername();
        $can_edit_email = $profile->getCanEditEmail();
        $can_edit_realname = $profile->getCanEditRealName();

        $must_set_password = $provider->shouldRequireRegistrationPassword();

        $can_edit_anything = $profile->getCanEditAnything() || $must_set_password;
        $force_verify = $profile->getShouldVerifyEmail();

        // Automatically verify the administrator's email address during first-time
        // setup.
        if ($is_setup) {
            $force_verify = true;
        }

        $value_username = $default_username;
        $value_realname = $default_realname;
        $value_email = $default_email;
        $value_password = null;

        $require_real_name = PhabricatorEnv::getEnvConfig('user.require-real-name');

        $e_username = strlen($value_username) ? null : true;
        $e_realname = $require_real_name ? true : null;
        $e_email = strlen($value_email) ? null : true;
        $e_password = true;
        $e_captcha = true;

        $skip_captcha = false;
        if ($invite) {
            // If the user is accepting an invite, assume they're trustworthy enough
            // that we don't need to CAPTCHA them.
            $skip_captcha = true;
        }

        $min_len = PhabricatorEnv::getEnvConfig('account.minimum-password-length');
        $min_len = (int)$min_len;

        $from_invite = $request->getStr('invite');
        if ($from_invite && $can_edit_username) {
            $value_username = $request->getStr('username');
            $e_username = null;
        }

        $try_register =
            ($request->isFormPost() || !$can_edit_anything) &&
            !$from_invite &&
            ($request->getInt('phase') != 1);

        if ($try_register) {
            $errors = array();

            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

            if ($must_set_password && !$skip_captcha) {
                $e_captcha = \Yii::t("app", 'Again');

                $captcha_ok = AphrontFormRecaptchaControl::processCaptcha($request);
                if (!$captcha_ok) {
                    $errors[] = \Yii::t("app", 'Captcha response is incorrect, try again.');
                    $e_captcha = \Yii::t("app", 'Invalid');
                }
            }

            if ($can_edit_username) {
                $value_username = $request->getStr('username');
                if (!strlen($value_username)) {
                    $e_username = \Yii::t("app", 'Required');
                    $errors[] = \Yii::t("app", 'Username is required.');
                } else if (!PhabricatorUser::validateUsername($value_username)) {
                    $e_username = \Yii::t("app", 'Invalid');
                    $errors[] = PhabricatorUser::describeValidUsername();
                } else {
                    $e_username = null;
                }
            }

            if ($must_set_password) {
                $value_password = $request->getStr('password');
                $value_confirm = $request->getStr('confirm');

                $password_envelope = new PhutilOpaqueEnvelope($value_password);
                $confirm_envelope = new PhutilOpaqueEnvelope($value_confirm);

                $engine = (new PhabricatorAuthPasswordEngine())
                    ->setViewer($user)
                    ->setContentSource($content_source)
                    ->setPasswordType($account_type)
                    ->setObject($user);

                try {
                    $engine->checkNewPassword($password_envelope, $confirm_envelope);
                    $e_password = null;
                } catch (PhabricatorAuthPasswordException $ex) {
                    $errors[] = $ex->getMessage();
                    $e_password = $ex->getPasswordError();
                }
            }

            if ($can_edit_email) {
                $value_email = $request->getStr('email');
                if (!strlen($value_email)) {
                    $e_email = \Yii::t("app", 'Required');
                    $errors[] = \Yii::t("app", 'Email is required.');
                } else if (!PhabricatorUserEmail::isValidAddress($value_email)) {
                    $e_email = \Yii::t("app", 'Invalid');
                    $errors[] = PhabricatorUserEmail::describeValidAddresses();
                } else if (!PhabricatorUserEmail::isAllowedAddress($value_email)) {
                    $e_email = \Yii::t("app", 'Disallowed');
                    $errors[] = PhabricatorUserEmail::describeAllowedAddresses();
                } else {
                    $e_email = null;
                }
            }

            if ($can_edit_realname) {
                $value_realname = $request->getStr('realName');
                if (!strlen($value_realname) && $require_real_name) {
                    $e_realname = \Yii::t("app", 'Required');
                    $errors[] = \Yii::t("app", 'Real name is required.');
                } else {
                    $e_realname = null;
                }
            }

            if (!$errors) {
                $image = $this->loadProfilePicture($account);
                if ($image) {
                    $user->setProfileImagePHID($image->getPHID());
                }

                try {
                    $verify_email = false;

                    if ($force_verify) {
                        $verify_email = true;
                    }

                    if ($value_email === $default_email) {
                        if ($account->getEmailVerified()) {
                            $verify_email = true;
                        }

                        if ($provider->should_trust_emails()) {
                            $verify_email = true;
                        }

                        if ($invite) {
                            $verify_email = true;
                        }
                    }

                    $email_obj = null;
                    if ($invite) {
                        // If we have a valid invite, this email may exist but be
                        // nonprimary and unverified, so we'll reassign it.
                        $email_obj = PhabricatorUserEmail::find()->andWhere(['address' => $value_email])->one();
                    }
                    if (!$email_obj) {
                        $email_obj = (new PhabricatorUserEmail())
                            ->setAddress($value_email);
                    }

                    $email_obj->setIsVerified((int)$verify_email);

                    $user->setUsername($value_username);
                    $user->setRealname($value_realname);

                    if ($is_setup) {
                        $must_approve = false;
                    } else if ($invite) {
                        $must_approve = false;
                    } else {
                        $must_approve = PhabricatorEnv::getEnvConfig(
                            'auth.require-approval');
                    }

                    if ($must_approve) {
                        $user->setIsApproved(0);
                    } else {
                        $user->setIsApproved(1);
                    }

                    if ($invite) {
                        $allow_reassign_email = true;
                    } else {
                        $allow_reassign_email = false;
                    }

                    $user->openTransaction();

                    $editor = (new PhabricatorUserEditor())
                        ->setActor($user);

                    $editor->createNewUser($user, $email_obj, $allow_reassign_email);
                    if ($must_set_password) {
                        $password_object = PhabricatorAuthPassword::initializeNewPassword(
                            $user,
                            $account_type);

                        $password_object
                            ->setPassword($password_envelope, $user)
                            ->save();
                    }

                    if ($is_setup) {
                        $editor->makeAdminUser($user, true);
                    }

                    $account->setUserPHID($user->getPHID());
                    $provider->willRegisterAccount($account);
                    $account->save();

                    $user->saveTransaction();

                    if (!$email_obj->getIsVerified()) {
                        $email_obj->sendVerificationEmail($user);
                    }

                    if ($must_approve) {
                        $this->sendWaitingForApprovalEmail($user);
                    }

                    if ($invite) {
                        $invite->setAcceptedByPHID($user->getPHID())->save();
                    }

                    return $this->loginUser($user);
                } catch (AphrontDuplicateKeyQueryException $exception) {
                    $same_username = PhabricatorUser::find()->andWhere([
                        'userName' => $user->getUserName()
                    ])->one();

                    $same_email = PhabricatorUserEmail::find()->andWhere([
                        "address" => $value_email,
                    ])->one();

                    if ($same_username) {
                        $e_username = \Yii::t("app", 'Duplicate');
                        $errors[] = \Yii::t("app", 'Another user already has that username.');
                    }

                    if ($same_email) {
                        // TODO: See T3340.
                        $e_email = \Yii::t("app", 'Duplicate');
                        $errors[] = \Yii::t("app", 'Another user already has that email.');
                    }

                    if (!$same_username && !$same_email) {
                        throw $exception;
                    }
                }
            }

            unset($unguarded);
        }

        $form = (new AphrontFormView())
            ->setViewer($request->getViewer())
            ->addHiddenInput('phase', 2);

        if (!$is_default) {
            $form->appendChild(
                (new AphrontFormMarkupControl())
                    ->setLabel(\Yii::t("app", 'External Account'))
                    ->setValue(
                        (new PhabricatorAuthAccountView())
                            ->setViewer($request->getViewer())
                            ->setExternalAccount($account)
                            ->setAuthProvider($provider)));
        }


        if ($can_edit_username) {
            $form->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Username'))
                    ->setName('username')
                    ->setValue($value_username)
                    ->setError($e_username));
        } else {
            $form->appendChild(
                (new AphrontFormMarkupControl())
                    ->setLabel(\Yii::t("app", 'Username'))
                    ->setValue($value_username)
                    ->setError($e_username));
        }

        if ($can_edit_realname) {
            $form->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Real Name'))
                    ->setName('realName')
                    ->setValue($value_realname)
                    ->setError($e_realname));
        }

        if ($must_set_password) {
            $form->appendChild(
                (new AphrontFormPasswordControl())
                    ->setLabel(\Yii::t("app", 'Password'))
                    ->setName('password')
                    ->setError($e_password));
            $form->appendChild(
                (new AphrontFormPasswordControl())
                    ->setLabel(\Yii::t("app", 'Confirm Password'))
                    ->setName('confirm')
                    ->setError($e_password)
                    ->setCaption(
                        $min_len
                            ? \Yii::t("app", 'Minimum length of {0} characters.', [
                            $min_len
                        ])
                            : null));
        }

        if ($can_edit_email) {
            $form->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Email'))
                    ->setName('email')
                    ->setValue($value_email)
                    ->setCaption(PhabricatorUserEmail::describeAllowedAddresses())
                    ->setError($e_email));
        }

        if ($must_set_password && !$skip_captcha) {
            $form->appendChild(
                (new AphrontFormRecaptchaControl())
                    ->setLabel(\Yii::t("app", 'Captcha'))
                    ->setError($e_captcha));
        }

        $submit = (new AphrontFormSubmitControl());

        if ($is_setup) {
            $submit
                ->setValue(\Yii::t("app", 'Create Admin Account'));
        } else {
            $submit
                ->addCancelButton($this->getApplicationURI('start/'))
                ->setValue(\Yii::t("app", 'Register Account'));
        }


        $form->appendChild($submit);

        $crumbs = $this->buildApplicationCrumbs();

        if ($is_setup) {
            $crumbs->addTextCrumb(\Yii::t("app", 'Setup Admin Account'));
            $title = \Yii::t("app", 'Welcome to {0}', PhabricatorEnv::getEnvConfig("orangins.site-name"));
        } else {
            $crumbs->addTextCrumb(\Yii::t("app", 'Register'));
            $crumbs->addTextCrumb($provider->getProviderName());
            $title = \Yii::t("app", 'Create a New Account');
        }
        $crumbs->setBorder(true);

        $welcome_view = null;
        if ($is_setup) {
            $welcome_view = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->setTitle(\Yii::t("app", 'Welcome to {0}', PhabricatorEnv::getEnvConfig("orangins.site-name")))
                ->appendChild(
                    \Yii::t("app",
                        'Installation is complete. Register your administrator account ' .
                        'below to log in. You will be able to configure options and add ' .
                        'other authentication mechanisms (like LDAP or OAuth) later on.'));
        }

        $object_box = (new PHUIObjectBoxView())
            ->setForm($form)
            ->setFormErrors($errors);

        $invite_header = null;
        if ($invite) {
            $invite_header = $this->renderInviteHeader($invite);
        }

        $header = (new PHUIPageHeaderView())
            ->setHeader($title);

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $welcome_view,
                $invite_header,
                $object_box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function loadDefaultAccount()
    {
        $providers = PhabricatorAuthProvider::getAllEnabledProviders();
        $account = null;
        $provider = null;
        $response = null;

        foreach ($providers as $key => $candidate_provider) {
            if (!$candidate_provider->shouldAllowRegistration()) {
                unset($providers[$key]);
                continue;
            }
            if (!$candidate_provider->isDefaultRegistrationProvider()) {
                unset($providers[$key]);
            }
        }

        if (!$providers) {
            $response = $this->renderError(
                \Yii::t("app",
                    'There are no configured default registration providers.'));
            return array($account, $provider, $response);
        } else if (count($providers) > 1) {
            $response = $this->renderError(
                \Yii::t("app", 'There are too many configured default registration providers.'));
            return array($account, $provider, $response);
        }

        /** @var PhabricatorAuthProvider $provider */
        $provider = OranginsUtil::head($providers);
        $account = $provider->getDefaultExternalAccount();

        return array($account, $provider, $response);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function loadSetupAccount()
    {
        $provider = new PhabricatorPasswordAuthProvider();
        $provider->attachProviderConfig(
            (new PhabricatorAuthProviderConfig())
                ->setShouldAllowRegistration(1)
                ->setShouldAllowLogin(1)
                ->setIsEnabled(true));

        $account = $provider->getDefaultExternalAccount();
        $response = null;
        return array($account, $provider, $response);
    }



    /**
     * @param $message
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderError($message)
    {
        return $this->renderErrorPage(
            \Yii::t("app", 'Registration Failed'),
            array($message));
    }

    /**
     * @param PhabricatorUser $user
     * @throws InvalidConfigException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    private function sendWaitingForApprovalEmail(PhabricatorUser $user)
    {
        $title = '[Phabricator] ' . \Yii::t("app",
                'New User "%s" Awaiting Approval',
                $user->getUsername());

        $body = new PhabricatorMetaMTAMailBody();

        $body->addRawSection(
            \Yii::t("app",
                'Newly registered user "%s" is awaiting account approval by an ' .
                'administrator.',
                $user->getUsername()));

        $body->addLinkSection(
            \Yii::t("app", 'APPROVAL QUEUE'),
            PhabricatorEnv::getProductionURI(
                '/people/query/approval/'));

        $body->addLinkSection(
            \Yii::t("app", 'DISABLE APPROVAL QUEUE'),
            PhabricatorEnv::getProductionURI(
                '/config/edit/auth.require-approval/'));

        $admins = PhabricatorUser::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withIsAdmin(true)
            ->execute();

        if (!$admins) {
            return;
        }

        $mail = (new PhabricatorMetaMTAMail())
            ->addTos(OranginsUtil::mpull($admins, 'getPHID'))
            ->setSubject($title)
            ->setBody($body->render())
            ->saveAndSend();
    }

}
