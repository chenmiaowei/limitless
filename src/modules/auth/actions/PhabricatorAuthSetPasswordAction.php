<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontResponse;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\auth\password\PhabricatorAuthPasswordException;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\auth\tokentype\PhabricatorAuthPasswordResetTemporaryTokenType;
use orangins\lib\env\PhabricatorEnv;
use PhutilOpaqueEnvelope;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;

/**
 * Class PhabricatorAuthSetPasswordAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthSetPasswordAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPartialSessions()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowLegallyNonCompliantUsers()
    {
        return true;
    }

    /**
     * @return AphrontResponse|PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        if (!PhabricatorPasswordAuthProvider::getPasswordProvider()) {
            return new Aphront404Response();
        }

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            '/');

        $key = $request->getStr('key');
        $password_type = PhabricatorAuthPasswordResetTemporaryTokenType::TOKENTYPE;
        if (!$key) {
            return new Aphront404Response();
        }

        $auth_token = PhabricatorAuthTemporaryToken::find()
            ->setViewer($viewer)
            ->withTokenResources(array($viewer->getPHID()))
            ->withTokenTypes(array($password_type))
            ->withTokenCodes(array(PhabricatorHash::weakDigest($key)))
            ->withExpired(false)
            ->executeOne();
        if (!$auth_token) {
            return new Aphront404Response();
        }

        $content_source = PhabricatorContentSource::newFromRequest($request);
        $account_type = PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT;

        $password_objects = PhabricatorAuthPassword::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($viewer->getPHID()))
            ->withPasswordTypes(array($account_type))
            ->withIsRevoked(false)
            ->execute();
        if ($password_objects) {
            $password_object = OranginsUtil::head($password_objects);
            $has_password = true;
        } else {
            $password_object = PhabricatorAuthPassword::initializeNewPassword(
                $viewer,
                $account_type);
            $has_password = false;
        }

        $engine = (new PhabricatorAuthPasswordEngine())
            ->setViewer($viewer)
            ->setContentSource($content_source)
            ->setPasswordType($account_type)
            ->setObject($viewer);

        $e_password = true;
        $e_confirm = true;
        $errors = array();
        if ($request->isFormPost()) {
            $password = $request->getStr('password');
            $confirm = $request->getStr('confirm');

            $password_envelope = new PhutilOpaqueEnvelope($password);
            $confirm_envelope = new PhutilOpaqueEnvelope($confirm);

            try {
                $engine->checkNewPassword($password_envelope, $confirm_envelope, true);
                $e_password = null;
                $e_confirm = null;
            } catch (PhabricatorAuthPasswordException $ex) {
                $errors[] = $ex->getMessage();
                $e_password = $ex->getPasswordError();
                $e_confirm = $ex->getConfirmError();
            }

            if (!$errors) {
                $password_object
                    ->setPassword($password_envelope, $viewer)
                    ->save();

                // Destroy the token.
                $auth_token->delete();

                return (new AphrontRedirectResponse())->setURI('/');
            }
        }

        $min_len = PhabricatorEnv::getEnvConfig('account.minimum-password-length');
        $min_len = (int)$min_len;

        $len_caption = null;
        if ($min_len) {
            $len_caption = \Yii::t("app", 'Minimum password length: {0} characters.', [$min_len]);
        }

        if ($has_password) {
            $title = \Yii::t("app", 'Reset Password');
            $crumb = \Yii::t("app", 'Reset Password');
            $submit = \Yii::t("app", 'Reset Password');
        } else {
            $title = \Yii::t("app", 'Set Password');
            $crumb = \Yii::t("app", 'Set Password');
            $submit = \Yii::t("app", 'Set Account Password');
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->addHiddenInput('key', $key)
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setDisableAutocomplete(true)
                    ->setLabel(\Yii::t("app", 'New Password'))
                    ->setError($e_password)
                    ->setName('password'))
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setDisableAutocomplete(true)
                    ->setLabel(\Yii::t("app", 'Confirm Password'))
                    ->setCaption($len_caption)
                    ->setError($e_confirm)
                    ->setName('confirm'))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton('/', \Yii::t("app", 'Skip This Step'))
                    ->setValue($submit));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $main_view = (new PHUITwoColumnView())
            ->setFooter($form_box);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($crumb)
            ->setBorder(true);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($main_view);
    }
}
