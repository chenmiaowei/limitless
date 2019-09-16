<?php

namespace orangins\modules\settings\panel;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasher;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasherUnavailableException;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\auth\password\PhabricatorAuthPasswordException;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\settings\panelgroup\PhabricatorSettingsAuthenticationPanelGroup;
use orangins\modules\settings\systemaction\PhabricatorAuthChangePasswordAction;
use orangins\modules\system\engine\PhabricatorSystemActionEngine;
use PhutilOpaqueEnvelope;

/**
 * Class PhabricatorPasswordSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorPasswordSettingsPanel extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'password';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app", 'Password');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsAuthenticationPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function isEnabled()
    {
        // There's no sense in showing a change password panel if this install
        // doesn't support password authentication.
        if (!PhabricatorPasswordAuthProvider::getPasswordProvider()) {
            return false;
        }

        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return array|AphrontRedirectResponse
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();
        $user = $this->getUser();

        $content_source = PhabricatorContentSource::newFromRequest($request);

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            '/settings/');

        $min_len = PhabricatorEnv::getEnvConfig('account.minimum-password-length');
        $min_len = (int)$min_len;

        // NOTE: Users can also change passwords through the separate "set/reset"
        // interface which is reached by logging in with a one-time token after
        // registration or password reset. If this flow changes, that flow may
        // also need to change.

        $account_type = PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT;

        $password_objects = PhabricatorAuthPassword::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($user->getPHID()))
            ->withPasswordTypes(array($account_type))
            ->withIsRevoked(false)
            ->execute();
        if ($password_objects) {
            /** @var PhabricatorAuthPassword $password_object */
            $password_object = head($password_objects);
        } else {
            $password_object = PhabricatorAuthPassword::initializeNewPassword(
                $user,
                $account_type);
        }

        $e_old = true;
        $e_new = true;
        $e_conf = true;

        $errors = array();
        if ($request->isFormPost()) {
            // Rate limit guesses about the old password. This page requires MFA and
            // session compromise already, so this is mostly just to stop researchers
            // from reporting this as a vulnerability.
            PhabricatorSystemActionEngine::willTakeAction(
                array($viewer->getPHID()),
                new PhabricatorAuthChangePasswordAction(),
                1);

            $envelope = new PhutilOpaqueEnvelope($request->getStr('old_pw'));

            $engine = (new PhabricatorAuthPasswordEngine())
                ->setViewer($viewer)
                ->setContentSource($content_source)
                ->setPasswordType($account_type)
                ->setObject($user);

            if (!strlen($envelope->openEnvelope())) {
                $errors[] = \Yii::t("app", 'You must enter your current password.');
                $e_old = \Yii::t("app", 'Required');
            } else if (!$engine->isValidPassword($envelope)) {
                $errors[] = \Yii::t("app", 'The old password you entered is incorrect.');
                $e_old = \Yii::t("app", 'Invalid');
            } else {
                $e_old = null;

                // Refund the user an action credit for getting the password right.
                PhabricatorSystemActionEngine::willTakeAction(
                    array($viewer->getPHID()),
                    new PhabricatorAuthChangePasswordAction(),
                    -1);
            }

            $pass = $request->getStr('new_pw');
            $conf = $request->getStr('conf_pw');
            $password_envelope = new PhutilOpaqueEnvelope($pass);
            $confirm_envelope = new PhutilOpaqueEnvelope($conf);

            try {
                $engine->checkNewPassword($password_envelope, $confirm_envelope);
                $e_new = null;
                $e_conf = null;
            } catch (PhabricatorAuthPasswordException $ex) {
                $errors[] = $ex->getMessage();
                $e_new = $ex->getPasswordError();
                $e_conf = $ex->getConfirmError();
            }

            if (!$errors) {
                $password_object
                    ->setPassword($password_envelope, $user)
                    ->save();

                $next = $this->getPanelURI('?saved=true');

                (new PhabricatorAuthSessionEngine())->terminateLoginSessions(
                    $user,
                    $request->getCookie(PhabricatorCookies::COOKIE_SESSION));

                return (new AphrontRedirectResponse())->setURI($next);
            }
        }

        if ($password_object->getID()) {
            try {
                $can_upgrade = $password_object->canUpgrade();
            } catch (PhabricatorPasswordHasherUnavailableException $ex) {
                $can_upgrade = false;

                $errors[] = \Yii::t("app",
                    'Your password is currently hashed using an algorithm which is ' .
                    'no longer available on this install.');
                $errors[] = \Yii::t("app",
                    'Because the algorithm implementation is missing, your password ' .
                    'can not be used or updated.');
                $errors[] = \Yii::t("app",
                    'To set a new password, request a password reset link from the ' .
                    'login screen and then follow the instructions.');
            }

            if ($can_upgrade) {
                $errors[] = \Yii::t("app",
                    'The strength of your stored password hash can be upgraded. ' .
                    'To upgrade, either: log out and log in using your password; or ' .
                    'change your password.');
            }
        }

        $len_caption = null;
        if ($min_len) {
            $len_caption = \Yii::t("app", 'Minimum password length: {0} characters.', [$min_len]);
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setLabel(\Yii::t("app", 'Old Password'))
                    ->setError($e_old)
                    ->setName('old_pw'))
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setDisableAutocomplete(true)
                    ->setLabel(\Yii::t("app", 'New Password'))
                    ->setError($e_new)
                    ->setName('new_pw'))
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setDisableAutocomplete(true)
                    ->setLabel(\Yii::t("app", 'Confirm Password'))
                    ->setCaption($len_caption)
                    ->setError($e_conf)
                    ->setName('conf_pw'))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Change Password')));

        $properties = (new PHUIPropertyListView());

        $properties->addProperty(
            \Yii::t("app", 'Current Algorithm'),
            PhabricatorPasswordHasher::getCurrentAlgorithmName(
                $password_object->newPasswordEnvelope()));

        $properties->addProperty(
            \Yii::t("app", 'Best Available Algorithm'),
            PhabricatorPasswordHasher::getBestAlgorithmName());

        $info_view = (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
            ->appendChild(
                \Yii::t("app", 'Changing your password will terminate any other outstanding ' .
                    'login sessions.'));

        $algo_box = $this->newBox(\Yii::t("app", 'Password Algorithms'), $properties);
        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Change Password'))
            ->setFormSaved($request->getStr('saved'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        return array(
            $form_box,
//            $algo_box,
            $info_view,
        );
    }


}
