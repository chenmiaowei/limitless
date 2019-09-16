<?php

namespace orangins\modules\auth\actions;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\auth\tokentype\PhabricatorAuthPasswordResetTemporaryTokenType;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilSafeHTML;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthOneTimeLoginAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthOneTimeLoginAction
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
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $id = $request->getURIData('id');
        $link_type = $request->getURIData('type');
        $key = $request->getURIData('key');
        $email_id = $request->getURIData('emailID');

        if ($request->getViewer()->isLoggedIn()) {
            return $this->renderError(
                \Yii::t("app", 'You are already logged in.'));
        }

        /** @var PhabricatorUser $target_user */
        $target_user = PhabricatorUser::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withIDs(array($id))
            ->executeOne();
        if (!$target_user) {
            return new Aphront404Response();
        }

        // NOTE: As a convenience to users, these one-time login URIs may also
        // be associated with an email address which will be verified when the
        // URI is used.

        // This improves the new user experience for users receiving "Welcome"
        // emails on installs that require verification: if we did not verify the
        // email, they'd immediately get roadblocked with a "Verify Your Email"
        // error and have to go back to their email account, wait for a
        // "Verification" email, and then click that link to actually get access to
        // their account. This is hugely unwieldy, and if the link was only sent
        // to the user's email in the first place we can safely verify it as a
        // side effect of login.

        // The email hashed into the URI so users can't verify some email they
        // do not own by doing this:
        //
        //  - Add some address you do not own;
        //  - request a password reset;
        //  - change the URI in the email to the address you don't own;
        //  - login via the email link; and
        //  - get a "verified" address you don't control.

        $target_email = null;
        if ($email_id) {
            $target_email = PhabricatorUserEmail::find()->andWhere([
                'user_phid' => $target_user->getPHID(),
                'id' => $email_id
            ])->one();
            if (!$target_email) {
                return new Aphront404Response();
            }
        }

        $engine = new PhabricatorAuthSessionEngine();
        $token = $engine->loadOneTimeLoginKey(
            $target_user,
            $target_email,
            $key);

        if (!$token) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Unable to Log In'))
                ->setShortTitle(\Yii::t("app", 'Login Failure'))
                ->appendParagraph(
                    \Yii::t("app",
                        'The login link you clicked is invalid, out of date, or has ' .
                        'already been used.'))
                ->appendParagraph(
                    \Yii::t("app",
                        'Make sure you are copy-and-pasting the entire link into ' .
                        'your browser. Login links are only valid for 24 hours, and ' .
                        'can only be used once.'))
                ->appendParagraph(
                    \Yii::t("app", 'You can try again, or request a new link via email.'))
                ->addCancelButton(Url::to(['/auth/login/email']), \Yii::t("app", 'Send Another Email'));
        }

        if (!$target_user->canEstablishWebSessions()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Unable to Establish Web Session'))
                ->setShortTitle(\Yii::t("app", 'Login Failure'))
                ->appendParagraph(
                    \Yii::t("app",
                        'You are trying to gain access to an account ("%s") that can not ' .
                        'establish a web session.',
                        $target_user->getUsername()))
                ->appendParagraph(
                    \Yii::t("app",
                        'Special users like daemons and mailing lists are not permitted ' .
                        'to log in via the web. Log in as a normal user instead.'))
                ->addCancelButton('/');
        }

        if ($request->isFormPost()) {
            // If we have an email bound into this URI, verify email so that clicking
            // the link in the "Welcome" email is good enough, without requiring users
            // to go through a second round of email verification.

            $editor = (new PhabricatorUserEditor())
                ->setActor($target_user);

            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            // Nuke the token and all other outstanding password reset tokens.
            // There is no particular security benefit to destroying them all, but
            // it should reduce HackerOne reports of nebulous harm.
            $editor->revokePasswordResetLinks($target_user);

            if ($target_email) {
                $editor->verifyEmail($target_user, $target_email);
            }
            unset($unguarded);

            if (!PhabricatorPasswordAuthProvider::getPasswordProvider()) {
                $next = Url::to(['/settings/panel/external']);
            } else {

                // We're going to let the user reset their password without knowing
                // the old one. Generate a one-time token for that.
                $key = Filesystem::readRandomCharacters(16);
                $password_type =
                    PhabricatorAuthPasswordResetTemporaryTokenType::TOKENTYPE;

                $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                $x = new PhabricatorAuthTemporaryToken();
                $x
                    ->setTokenResource($target_user->getPHID())
                    ->setTokenType($password_type)
                    ->setTokenExpires(time() + phutil_units('1 hour in seconds'))
                    ->setTokenCode(PhabricatorHash::weakDigest($key))
                    ->save();
                unset($unguarded);

                $next = Url::to(['/auth/login/password', 'key' => $key]);
                $request->setTemporaryCookie(PhabricatorCookies::COOKIE_HISEC, 'yes');
            }

            PhabricatorCookies::setNextURICookie($request, $next, $force = true);

            return $this->loginUser($target_user);
        }

        // NOTE: We need to CSRF here so attackers can't generate an email link,
        // then log a user in to an account they control via sneaky invisible
        // form submissions.

        switch ($link_type) {
            case PhabricatorAuthSessionEngine::ONETIME_WELCOME:
                $title = \Yii::t("app", 'Welcome to {0}', PhabricatorEnv::getEnvConfig("orangins.site-name"));
                break;
            case PhabricatorAuthSessionEngine::ONETIME_RECOVER:
                $title = \Yii::t("app", 'Account Recovery');
                break;
            case PhabricatorAuthSessionEngine::ONETIME_USERNAME:
            case PhabricatorAuthSessionEngine::ONETIME_RESET:
            default:
                $title = \Yii::t("app", 'Log in to Phabricator');
                break;
        }

        $body = array();
        $body[] = new PhutilSafeHTML(\Yii::t("app",
            'Use the button below to log in as: {0}', [
                JavelinHtml::phutil_tag('strong', array(), $target_user->getUsername())
            ]));

        if ($target_email && !$target_email->getIsVerified()) {
            $body[] = new PhutilSafeHTML(\Yii::t("app",
                'Logging in will verify {0} as an email address you own.', [
                    JavelinHtml::phutil_tag('strong', array(), $target_email->getAddress())
                ]));

        }

        $body[] = \Yii::t("app",
            'After logging in you should set a password for your account, or ' .
            'link your account to an external account that you can use to ' .
            'authenticate in the future.');

        $dialog = $this->newDialog()
            ->setTitle($title)
            ->addSubmitButton(\Yii::t("app", 'Log In ({0})', [$target_user->getUsername()]))
            ->addCancelButton('/');

        foreach ($body as $paragraph) {
            $dialog->appendParagraph($paragraph);
        }

        return (new AphrontDialogResponse())->setDialog($dialog);
    }
}
