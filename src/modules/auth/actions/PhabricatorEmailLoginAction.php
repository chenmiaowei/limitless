<?php

namespace orangins\modules\auth\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront400Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\control\AphrontFormRecaptchaControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use yii\helpers\Url;

/**
 * Class PhabricatorEmailLoginAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorEmailLoginAction
    extends PhabricatorAuthAction
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
     * @return Aphront400Response|AphrontDialogView|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \AphrontQueryException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();

        if (!PhabricatorPasswordAuthProvider::getPasswordProvider()) {
            return new Aphront400Response();
        }

        $e_email = true;
        $e_captcha = true;
        $errors = array();

        $is_serious = PhabricatorEnv::getEnvConfig('orangins.serious-business');

        if ($request->isFormPost()) {
            $e_email = null;
            $e_captcha = \Yii::t("app", 'Again');

            $captcha_ok = AphrontFormRecaptchaControl::processCaptcha($request);
            if (!$captcha_ok) {
                $errors[] = \Yii::t("app", 'Captcha response is incorrect, try again.');
                $e_captcha = \Yii::t("app", 'Invalid');
            }

            $email = $request->getStr('email');
            if (!strlen($email)) {
                $errors[] = \Yii::t("app", 'You must provide an email address.');
                $e_email = \Yii::t("app", 'Required');
            }

            if (!$errors) {
                // NOTE: Don't validate the email unless the captcha is good; this makes
                // it expensive to fish for valid email addresses while giving the user
                // a better error if they goof their email.

                $target_email = PhabricatorUserEmail::find()->andWhere(['address' => $email])->one();


                $target_user = null;
                if ($target_email) {
                    $target_user = PhabricatorUser::find()->andWhere(['phid' => $target_email->getUserPHID()])->one();
                }

                if (!$target_user) {
                    $errors[] =
                        \Yii::t("app", 'There is no account associated with that email address.');
                    $e_email = \Yii::t("app", 'Invalid');
                }

                // If this address is unverified, only send a reset link to it if
                // the account has no verified addresses. This prevents an opportunistic
                // attacker from compromising an account if a user adds an email
                // address but mistypes it and doesn't notice.

                // (For a newly created account, all the addresses may be unverified,
                // which is why we'll send to an unverified address in that case.)

                if ($target_email && !$target_email->getIsVerified()) {
                    $verified_addresses = PhabricatorUserEmail::find()->andWhere([
                        'user_phid' => $target_email->getUserPHID(),
                        'is_verified' => 1
                    ])->one();
                    if ($verified_addresses) {
                        $errors[] = \Yii::t("app",
                            'That email address is not verified, but the account it is ' .
                            'connected to has at least one other verified address. When an ' .
                            'account has at least one verified address, you can only send ' .
                            'password reset links to one of the verified addresses. Try ' .
                            'a verified address instead.');
                        $e_email = \Yii::t("app", 'Unverified');
                    }
                }

                if (!$errors) {
                    $engine = new PhabricatorAuthSessionEngine();
                    $uri = $engine->getOneTimeLoginURI(
                        $target_user,
                        null,
                        PhabricatorAuthSessionEngine::ONETIME_RESET);

                    if ($is_serious) {
                        $body = \Yii::t("app",
                            "You can use this link to reset your Phabricator password:" .
                            "\n\n  %s\n",
                            $uri);
                    } else {
                        $body = \Yii::t("app",
                            "Condolences on forgetting your password. You can use this " .
                            "link to reset it:\n\n" .
                            "  {0}\n\n" .
                            "After you set a new password, consider writing it down on a " .
                            "sticky note and attaching it to your monitor so you don't " .
                            "forget again! Choosing a very short, easy-to-remember password " .
                            "like \"cat\" or \"1234\" might also help.\n\n" .
                            "Best Wishes,\n{1}\n",
                            [
                                $uri,
                                PhabricatorEnv::getEnvConfig("orangins.site-name")
                            ]);

                    }

                    (new PhabricatorMetaMTAMail())
                        ->setSubject(\Yii::t("app", '[Phabricator] Password Reset'))
                        ->setForceDelivery(true)
                        ->addRawTos(array($target_email->getAddress()))
                        ->setBody($body)
                        ->saveAndSend();

                    return $this->newDialog()
                        ->setTitle(\Yii::t("app", 'Check Your Email'))
                        ->setShortTitle(\Yii::t("app", 'Email Sent'))
                        ->appendParagraph(
                            \Yii::t("app", 'An email has been sent with a link you can use to log in.'))
                        ->addCancelButton('/', \Yii::t("app", 'Done'));
                }
            }
        }

        $error_view = null;
        if ($errors) {
            $error_view = new PHUIInfoView();
            $error_view->setErrors($errors);
        }

        $email_auth = new PHUIFormLayoutView();
        $email_auth->appendChild($error_view);
        $email_auth
            ->setUser($request->getViewer())
            ->setFullWidth(true)
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Email'))
                    ->setName('email')
                    ->setValue($request->getStr('email'))
                    ->setError($e_email))
            ->appendChild(
                (new AphrontFormRecaptchaControl())
                    ->setLabel(\Yii::t("app", 'Captcha'))
                    ->setError($e_captcha));

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Reset Password'));
        $crumbs->setBorder(true);

        $dialog = new AphrontDialogView();
        $dialog->setUser($request->getViewer());
        $dialog->setTitle(\Yii::t("app", 'Forgot Password / Email Login'));
        $dialog->appendChild($email_auth);
        $dialog->addSubmitButton(\Yii::t("app", 'Send Email'));
        $dialog->setSubmitURI(Url::to(['/auth/login/email']));

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Forgot Password'))
            ->setCrumbs($crumbs)
            ->appendChild($dialog);

    }

}
