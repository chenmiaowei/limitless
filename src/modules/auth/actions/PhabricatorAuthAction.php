<?php

namespace orangins\modules\auth\actions;

use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthInviteEngine;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\events\AuthWillLoginUserEvent;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\events\constant\PhabricatorEventType;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\transform\PhabricatorFileThumbnailTransform;
use orangins\modules\file\transform\PhabricatorFileTransform;
use orangins\modules\people\models\PhabricatorExternalAccount;
use PhutilURI;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\people\models\PhabricatorAuthInvite;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use Exception;

/**
 * Class PhabricatorAuthAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
abstract class PhabricatorAuthAction extends PhabricatorAction
{

    /**
     * @param $title
     * @param array $messages
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderErrorPage($title, array $messages)
    {
        $view = new PHUIInfoView();
        $view->setTitle($title);
        $view->setErrors($messages);

        return $this->newPage()
            ->setTitle($title)
            ->appendChild($view);
    }

    /**
     * @return bool
     * Returns true if this install is newly setup (i.e., there are no user
     * accounts yet). In this case, we enter a special mode to permit creation
     * of the first account form the web UI.
     *
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function isFirstTimeSetup()
    {
        // If there are any auth providers, this isn't first time setup, even if
        // we don't have accounts.
        if (PhabricatorAuthProvider::getAllEnabledProviders()) {
            return false;
        }

        // Otherwise, check if there are any user accounts. If not, we're in first
        // time setup.
        $any_users = PhabricatorUser::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->setLimit(1)
            ->execute();

        return !$any_users;
    }


    /**
     * Log a user into a web session and return an @{class:AphrontResponse} which
     * corresponds to continuing the login process.
     *
     * Normally, this is a redirect to the validation controller which makes sure
     * the user's cookies are set. However, event listeners can intercept this
     * event and do something else if they prefer.
     *
     * @param   PhabricatorUser $user User to log the viewer in as.
     * @return  AphrontResponse   Response which continues the login process.
     * @throws \yii\base\Exception
     * @throws \AphrontQueryException
     * @throws Exception
     */
    protected function loginUser(PhabricatorUser $user)
    {

        $response = $this->buildLoginValidateResponse($user);
        $session_type = PhabricatorUser::TYPE_WEB;

        $event = new AuthWillLoginUserEvent();
        $event
            ->setUser($user)
            ->setType($session_type)
            ->setResponse($response)
            ->setShouldLogin(true);
        \Yii::$app->trigger(PhabricatorEventType::TYPE_AUTH_WILLLOGINUSER, $event);

        $should_login = $event->getShouldLogin();
        if ($should_login) {
            $session_key = (new PhabricatorAuthSessionEngine())
                ->establishSession($session_type, $user->getPHID(), $partial = true);

            // NOTE: We allow disabled users to login and roadblock them later, so
            // there's no check for users being disabled here.

            $request = $this->getRequest();
            $request->setCookie(
                PhabricatorCookies::COOKIE_USERNAME,
                $user->getUsername());
            $request->setCookie(
                PhabricatorCookies::COOKIE_SESSION,
                $session_key);

            $this->clearRegistrationCookies();
        }
        $aphrontResponse = $event->getResponse();
        return $aphrontResponse;
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function clearRegistrationCookies()
    {
        $request = $this->getRequest();

        // Clear the registration key.
        $request->clearCookie(PhabricatorCookies::COOKIE_REGISTRATION);

        // Clear the client ID / OAuth state key.
        $request->clearCookie(PhabricatorCookies::COOKIE_CLIENTID);

        // Clear the invite cookie.
        $request->clearCookie(PhabricatorCookies::COOKIE_INVITE);
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildLoginValidateResponse(PhabricatorUser $user)
    {
        $uri = $this->getApplicationURI('index/validate');
        $validate_uri = new PhutilURI($uri);
        $validate_uri->setQueryParam('expect', $user->getUsername());

        return (new AphrontRedirectResponse())->setURI((string)$validate_uri);
    }

    /**
     * @param $message
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderError($message)
    {
        return $this->renderErrorPage(
            \Yii::t("app", 'Authentication Error'),
            array(
                $message,
            ));
    }

    /**
     * @param $account_key
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadAccountForRegistrationOrLinking($account_key)
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $account = null;
        $provider = null;
        $response = null;

        if (!$account_key) {
            $response = $this->renderError(
                \Yii::t("app", 'Request did not include account key.'));
            return array($account, $provider, $response);
        }

        // NOTE: We're using the omnipotent user because the actual user may not
        // be logged in yet, and because we want to tailor an error message to
        // distinguish between "not usable" and "does not exist". We do explicit
        // checks later on to make sure this account is valid for the intended
        // operation. This requires edit permission for completeness and consistency
        // but it won't actually be meaningfully checked because we're using the
        // omnipotent user.

        $account = PhabricatorExternalAccount::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withAccountSecrets(array($account_key))
            ->needImages(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();

        if (!$account) {
            $response = $this->renderError(\Yii::t("app", 'No valid linkable account.'));
            return array($account, $provider, $response);
        }

        if ($account->getUserPHID()) {
            if ($account->getUserPHID() != $viewer->getPHID()) {
                $response = $this->renderError(
                    \Yii::t("app",
                        'The account you are attempting to register or link is already ' .
                        'linked to another user.'));
            } else {
                $response = $this->renderError(
                    \Yii::t("app",
                        'The account you are attempting to link is already linked ' .
                        'to your account.'));
            }
            return array($account, $provider, $response);
        }

        $registration_key = $request->getCookie(PhabricatorCookies::COOKIE_REGISTRATION);

        // NOTE: This registration key check is not strictly necessary, because
        // we're only creating new accounts, not linking existing accounts. It
        // might be more hassle than it is worth, especially for email.
        //
        // The attack this prevents is getting to the registration screen, then
        // copy/pasting the URL and getting someone else to click it and complete
        // the process. They end up with an account bound to credentials you
        // control. This doesn't really let you do anything meaningful, though,
        // since you could have simply completed the process yourself.

        if (!$registration_key) {
            $response = $this->renderError(
                \Yii::t("app",
                    'Your browser did not submit a registration key with the request. ' .
                    'You must use the same browser to begin and complete registration. ' .
                    'Check that cookies are enabled and try again.'));
            return array($account, $provider, $response);
        }

        // We store the digest of the key rather than the key itself to prevent a
        // theoretical attacker with read-only access to the database from
        // hijacking registration sessions.

        $actual = $account->getProperty('registrationKey');
        $expect = PhabricatorHash::weakDigest($registration_key);
        if (!phutil_hashes_are_identical($actual, $expect)) {
            $response = $this->renderError(
                \Yii::t("app",
                    'Your browser submitted a different registration key than the one ' .
                    'associated with this account. You may need to clear your cookies.'));
            return array($account, $provider, $response);
        }


        $other_account = PhabricatorExternalAccount::find()
            ->andWhere([
                'account_type' => $account->getAccountType(),
                'account_domain' => $account->getAccountDomain(),
                'account_id' => $account->getAccountID(),
                'id' => $account->getID()
            ])->all();

        if ($other_account) {
            $response = $this->renderError(
                \Yii::t("app",
                    'The account you are attempting to register with already belongs ' .
                    'to another user.'));
            return array($account, $provider, $response);
        }

        $provider = PhabricatorAuthProvider::getEnabledProviderByKey(
            $account->getProviderKey());

        if (!$provider) {
            $response = $this->renderError(
                \Yii::t("app",
                    'The account you are attempting to register with uses a nonexistent ' .
                    'or disabled authentication provider (with key "{0}"). An ' .
                    'administrator may have recently disabled this provider.', [
                        $account->getProviderKey()
                    ]));
            return array($account, $provider, $response);
        }

        return array($account, $provider, null);
    }

    /**
     * @return PhabricatorAuthInvite
     * @author 陈妙威
     */
    protected function loadInvite()
    {
        $invite_cookie = PhabricatorCookies::COOKIE_INVITE;
        $invite_code = $this->getRequest()->getCookie($invite_cookie);
        if (!$invite_code) {
            return null;
        }

        $engine = (new PhabricatorAuthInviteEngine())
            ->setViewer($this->getViewer())
            ->setUserHasConfirmedVerify(true);

        try {
            return $engine->processInviteCode($invite_code);
        } catch (Exception $ex) {
            // If this fails for any reason, just drop the invite. In normal
            // circumstances, we gave them a detailed explanation of any error
            // before they jumped into this workflow.
            return null;
        }
    }

    /**
     * @param PhabricatorAuthInvite $invite
     * @return null
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInviteHeader(PhabricatorAuthInvite $invite)
    {
        // Since the user hasn't registered yet, they may not be able to see other
        // user accounts. Load the inviting user with the omnipotent viewer.
        $omnipotent_viewer = PhabricatorUser::getOmnipotentUser();

        $invite_author = PhabricatorUser::find()
            ->setViewer($omnipotent_viewer)
            ->withPHIDs(array($invite->getAuthorPHID()))
            ->needProfileImage(true)
            ->executeOne();

        // If we can't load the author for some reason, just drop this message.
        // We lose the value of contextualizing things without author details.
        if (!$invite_author) {
            return null;
        }

        $invite_item = (new PHUIObjectItemView())
            ->setHeader(\Yii::t("app", 'Welcome to Phabricator!'))
            ->setImageURI($invite_author->getProfileImageURI())
            ->addAttribute(
                \Yii::t("app",
                    '%s has invited you to join Phabricator.',
                    $invite_author->getFullName()));

        $invite_list = (new PHUIObjectItemListView())
            ->addItem($invite_item)
            ->setFlush(true);

        return (new PHUIBoxView())
            ->addMargin(PHUI::MARGIN_LARGE)
            ->appendChild($invite_list);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @return PhabricatorFile
     * @throws InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function loadProfilePicture(PhabricatorExternalAccount $account)
    {
        $phid = $account->getProfileImagePHID();
        if (!$phid) {
            return null;
        }

        // NOTE: Use of omnipotent user is okay here because the registering user
        // can not control the field value, and we can't use their user object to
        // do meaningful policy checks anyway since they have not registered yet.
        // Reaching this means the user holds the account secret key and the
        // registration secret key, and thus has permission to view the image.

        $file = PhabricatorFile::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$file) {
            return null;
        }

        $xform = PhabricatorFileTransform::getTransformByKey(
            PhabricatorFileThumbnailTransform::TRANSFORM_PROFILE);
        return $xform->executeTransform($file);
    }
}
