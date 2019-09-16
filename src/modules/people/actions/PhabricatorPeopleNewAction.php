<?php

namespace orangins\modules\people\actions;

use AphrontDuplicateKeyQueryException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormDividerControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\people\capability\PeopleCreateUsersCapability;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleNewAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleNewAction
    extends PhabricatorPeopleAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws Exception
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $type = $request->getURIData('type');
        $admin = $request->getViewer();

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $admin,
            $request,
            $this->getApplicationURI());

        $is_bot = false;
        $is_list = false;
        switch ($type) {
            case 'standard':
                $this->requireApplicationCapability(
                    PeopleCreateUsersCapability::CAPABILITY);
                break;
            case 'bot':
                $is_bot = true;
                break;
            case 'list':
                $is_list = true;
                break;
            default:
                return new Aphront404Response();
        }

        $user = new PhabricatorUser();
        $require_real_name = PhabricatorEnv::getEnvConfig('user.require-real-name');

        $e_username = true;
        $e_realname = $require_real_name ? true : null;
        $e_email = true;
        $errors = array();

        $welcome_checked = true;

        $new_email = null;

        if ($request->isFormPost()) {
            $welcome_checked = $request->getInt('welcome');

            $user->setUsername($request->getStr('username'));

            $new_email = $request->getStr('email');
            if (!strlen($new_email)) {
                $errors[] = \Yii::t("app", 'Email is required.');
                $e_email = \Yii::t("app", 'Required');
            } else if (!PhabricatorUserEmail::isAllowedAddress($new_email)) {
                $e_email = \Yii::t("app", 'Invalid');
                $errors[] = PhabricatorUserEmail::describeAllowedAddresses();
            } else {
                $e_email = null;
            }

            $user->setRealName($request->getStr('realname'));

            if (!strlen($user->getUsername())) {
                $errors[] = \Yii::t("app", 'Username is required.');
                $e_username = \Yii::t("app", 'Required');
            } else if (!PhabricatorUser::validateUsername($user->getUsername())) {
                $errors[] = PhabricatorUser::describeValidUsername();
                $e_username = \Yii::t("app", 'Invalid');
            } else {
                $e_username = null;
            }

            if (!strlen($user->getRealName()) && $require_real_name) {
                $errors[] = \Yii::t("app", 'Real name is required.');
                $e_realname = \Yii::t("app", 'Required');
            } else {
                $e_realname = null;
            }

            if (!$errors) {
                try {

                    $email = (new PhabricatorUserEmail())
                        ->setAddress($new_email)
                        ->setIsVerified(0);

                    // Automatically approve the user, since an admin is creating them.
                    $user->setIsApproved(1);

                    // If the user is a bot or list, approve their email too.
                    if ($is_bot || $is_list) {
                        $email->setIsVerified(1);
                    }

                    (new PhabricatorUserEditor())
                        ->setActor($admin)
                        ->createNewUser($user, $email);

                    if ($is_bot) {
                        (new PhabricatorUserEditor())
                            ->setActor($admin)
                            ->makeSystemAgentUser($user, true);
                    }

                    if ($is_list) {
                        (new PhabricatorUserEditor())
                            ->setActor($admin)
                            ->makeMailingListUser($user, true);
                    }

                    if ($welcome_checked && !$is_bot && !$is_list) {
                        $user->sendWelcomeEmail($admin);
                    }

                    $response = (new AphrontRedirectResponse())
                        ->setURI(Url::to(['/people/index/view', 'username' => $user->getUsername()]));
                    return $response;
                } catch (AphrontDuplicateKeyQueryException $ex) {
                    $errors[] = \Yii::t("app", 'Username and email must be unique.');

                    $same_username = PhabricatorUser::find()
                        ->andWhere([
                            'username' => $user->getUsername(),
                        ])->one();
                    $same_email = PhabricatorUserEmail::find()
                        ->andWhere([
                            'address' => $new_email,
                        ])->one();
                    if ($same_username) {
                        $e_username = \Yii::t("app", 'Duplicate');
                    }

                    if ($same_email) {
                        $e_email = \Yii::t("app", 'Duplicate');
                    }
                } catch (Exception $e) {
                }
            }
        }

        $form = (new AphrontFormView())
            ->setUser($admin);

        if ($is_bot) {
            $title = \Yii::t("app", 'Create New Bot');
            $form->appendRemarkupInstructions(
                \Yii::t("app", 'You are creating a new **bot** user account.'));
        } else if ($is_list) {
            $title = \Yii::t("app", 'Create New Mailing List');
            $form->appendRemarkupInstructions(
                \Yii::t("app", 'You are creating a new **mailing list** user account.'));
        } else {
            $title = \Yii::t("app", 'Create New User');
            $form->appendRemarkupInstructions(
                \Yii::t("app", 'You are creating a new **standard** user account.'));
        }

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Username'))
                    ->setName('username')
                    ->setValue($user->getUsername())
                    ->setError($e_username))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Real Name'))
                    ->setName('realname')
                    ->setValue($user->getRealName())
                    ->setError($e_realname))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Email'))
                    ->setName('email')
                    ->setValue($new_email)
                    ->setCaption(PhabricatorUserEmail::describeAllowedAddresses())
                    ->setError($e_email));

        if (!$is_bot && !$is_list) {
            $form->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'welcome',
                        1,
                        \Yii::t("app", 'Send "Welcome to {0}" email with login instructions.', [
                        PhabricatorEnv::getEnvConfig("orangins.site-name")
                        ]),
                        $welcome_checked));
        }

        $form
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($this->getApplicationURI())
                    ->setValue(\Yii::t("app", 'Create User')));

        if ($is_bot) {
            $form
                ->appendChild((new AphrontFormDividerControl()))
                ->appendRemarkupInstructions(
                    \Yii::t("app",
                        '**Why do bot accounts need an email address?**' .
                        "\n\n" .
                        'Although bots do not normally receive email from Phabricator, ' .
                        'they can interact with other systems which require an email ' .
                        'address. Examples include:' .
                        "\n\n" .
                        "  - If the account takes actions which //send// email, we need " .
                        "    an address to use in the //From// header.\n" .
                        "  - If the account creates commits, Git and Mercurial require " .
                        "    an email address for authorship.\n" .
                        "  - If you send email //to// Phabricator on behalf of the " .
                        "    account, the address can identify the sender.\n" .
                        "  - Some internal authentication functions depend on accounts " .
                        "    having an email address.\n" .
                        "\n\n" .
                        "The address will automatically be verified, so you do not need " .
                        "to be able to receive mail at this address, and can enter some " .
                        "invalid or nonexistent (but correctly formatted) address like " .
                        "`bot@yourcompany.com` if you prefer."));
        }

        $box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $view = (new PHUITwoColumnView())
            ->setFooter($box);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
