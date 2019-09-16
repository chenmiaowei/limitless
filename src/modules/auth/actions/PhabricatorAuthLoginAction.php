<?php

namespace orangins\modules\auth\actions;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\response\AphrontPureJSONResponse;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\oauthserver\models\PhabricatorOAuthClientAuthorization;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\PhabricatorOAuthServer;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;
use PhutilAuthUserAbortedException;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthLoginAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthLoginAction extends PhabricatorAuthAction
{

    /**
     * @var
     */
    private $providerKey;
    /**
     * @var
     */
    private $extraURIData;
    /**
     * @var PhabricatorAuthProvider
     */
    private $provider;
    /**
     * @var bool
     */
    public $enableCsrfValidation = false;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @param $parameter_name
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowRestrictedParameter($parameter_name)
    {
        // Whitelist the OAuth 'code' parameter.

        if ($parameter_name == 'code') {
            return true;
        }
        return parent::shouldAllowRestrictedParameter($parameter_name);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getExtraURIData()
    {
        return $this->extraURIData;
    }

    /**
     * @return Aphront400Response|AphrontDialogResponse|AphrontPureJSONResponse|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $this->providerKey = $request->getURIData('pkey');
        $this->extraURIData = $request->getURIData('extra');

        $response = $this->loadProvider();
        if ($response) {
            return $response;
        }

        /** @var PhabricatorExternalAccount $account */
        $account = null;
        $provider = $this->provider;
        try {
            list($account, $response) = $provider->processLoginRequest($this);
        } catch (PhutilAuthUserAbortedException $ex) {
            if ($viewer->isLoggedIn()) {
                // If a logged-in user cancels, take them back to the external accounts
                // panel.
                $next_uri = Url::to(['/settings/panel/external']);
            } else {
                // If a logged-out user cancels, take them back to the auth start page.
                $next_uri = Url::to(['/']);
            }

            // User explicitly hit "Cancel".
            $dialog = (new AphrontDialogView())
                ->setUser($viewer)
                ->setTitle(\Yii::t("app", 'Authentication Canceled'))
                ->appendChild(
                    \Yii::t("app", 'You canceled authentication.'))
                ->addCancelButton($next_uri, \Yii::t("app", 'Continue'));
            return (new AphrontDialogResponse())->setDialog($dialog);
        }

        if ($response) {
            return $response;
        }

        if (!$account) {
            throw new Exception(
                \Yii::t("app",
                    'Auth provider failed to load an account from {0}!', [
                        'processLoginRequest()'
                    ]));
        }

        if ($provider->autoRegister()) {
            if ($provider->shouldAllowRegistration()) {

                if (!$account->getUserPHID()) {
                    $user = new PhabricatorUser();
                    $image = $this->loadProfilePicture($account);
                    if ($image) {
                        $user->setProfileImagePHID($image->getPHID());
                    }

                    $username = $account->getUsername();
                    $username = $username === null ? PhabricatorHash::digestForIndex($account->getAccountId()) : $username;
                    $user->setUsername($username);
                    $user->setRealname($account->getRealName());


                    $user->setIsApproved(1);
                    $user->openTransaction();
                    $editor = (new PhabricatorUserEditor())
                        ->setActor($user);

                    $editor->createNewUserWithoutEmail($user);
                    $account->setUserPHID($user->getPHID());
                    $provider->willRegisterAccount($account);
                    $account->save();

                    $user->saveTransaction();
                }

                $user = PhabricatorUser::find()
                    ->andWhere(['phid' => $account->getUserPHID()])
                    ->one();

                $client = PhabricatorOAuthServerClient::find()
                    ->andWhere([
                        'is_system' => 1
                    ])
                    ->one();
                if (!$client) {
                    $client = (new  PhabricatorOAuthServerClient())
                        ->setName('系统客户端')
                        ->setCreatorPHID('')
                        ->setRedirectURI('')
                        ->setIsSystem(1)
                        ->setSecret(Filesystem::readRandomCharacters(32))
                        ->setViewPolicy(PhabricatorPolicies::POLICY_USER)
                        ->setEditPolicy(PhabricatorPolicies::POLICY_ADMIN)
                        ->setIsDisabled(0)
                        ->setIsTrusted(0);

                    if (!$client->save()) {
                        throw new ActiveRecordException('Create client error', $client->getErrorSummary(true));
                    }
                }

                $server = new PhabricatorOAuthServer();
                $server->setClient($client);
                $server->setUser($user);
                $access_token = $server->generateAccessToken();


                /** @var PhabricatorOAuthClientAuthorization $authorization */
                $authorization = PhabricatorOAuthClientAuthorization::find()
                    ->andWhere([
                        'user_phid' => $user->getPHID(),
                        'client_phid' => $client->getPHID()
                    ])->one();
                if (!$authorization) {
                    $server->authorizeClient([]);
                }

                return (new AphrontPureJSONResponse())
                    ->setContent([
                        'result' => [
                            'access_token' => $access_token->token,
                            'user_phid' => $user->getPHID(),
                            'real_name' => $user->real_name
                        ]
                    ]);
            } else {
                return $this->renderError(
                    \Yii::t("app",
                        'The external account ("{0}") you just authenticated with is ' .
                        'not configured to allow registration on this Phabricator ' .
                        'install. An administrator may have recently disabled it.', [
                            $provider->getProviderName()
                        ]));
            }
        } else {

            if ($account->getUserPHID()) {
                // The account is already attached to a Phabricator user, so this is
                // either a login or a bad account link request.
                if (!$viewer->isLoggedIn()) {
                    if ($provider->shouldAllowLogin()) {
                        return $this->processLoginUser($account);
                    } else {
                        return $this->renderError(
                            \Yii::t("app",
                                'The external account ("%s") you just authenticated with is ' .
                                'not configured to allow logins on this Phabricator install. ' .
                                'An administrator may have recently disabled it.',
                                $provider->getProviderName()));
                    }
                } else
                    if ($viewer->getPHID() == $account->getUserPHID()) {
                        // This is either an attempt to re-link an existing and already
                        // linked account (which is silly) or a refresh of an external account
                        // (e.g., an OAuth account).
                        return (new AphrontRedirectResponse())->setURI(Url::to(['/settings/panel/external']));
                    } else {
                        return $this->renderError(
                            \Yii::t("app",
                                'The external account ("{0}") you just used to log in is already ' .
                                'associated with another Phabricator user account. Log in to the ' .
                                'other Phabricator account and unlink the external account before ' .
                                'linking it to a new Phabricator account.', [
                                    $provider->getProviderName()
                                ]));
                    }
            } else {
                // The account is not yet attached to a Phabricator user, so this is
                // either a registration or an account link request.
                if (!$viewer->isLoggedIn()) {
                    if ($provider->shouldAllowRegistration()) {
                        return $this->processRegisterUser($account);
                    } else {
                        return $this->renderError(
                            \Yii::t("app",
                                'The external account ("{0}") you just authenticated with is ' .
                                'not configured to allow registration on this Phabricator ' .
                                'install. An administrator may have recently disabled it.', [
                                    $provider->getProviderName()
                                ]));
                    }
                } else {

                    // If the user already has a linked account of this type, prevent them
                    // from linking a second account. This can happen if they swap logins
                    // and then refresh the account link. See T6707. We will eventually
                    // allow this after T2549.
                    $existing_accounts = PhabricatorExternalAccount::find()
                        ->setViewer($viewer)
                        ->withUserPHIDs(array($viewer->getPHID()))
                        ->withAccountTypes(array($account->getAccountType()))
                        ->execute();
                    if ($existing_accounts) {
                        return $this->renderError(
                            \Yii::t("app",
                                'Your Phabricator account is already connected to an external ' .
                                'account on this provider ("{0}"), but you are currently logged ' .
                                'in to the provider with a different account. Log out of the ' .
                                'external service, then log back in with the correct account ' .
                                'before refreshing the account link.', [
                                    $provider->getProviderName()
                                ]));
                    }

                    if ($provider->shouldAllowAccountLink()) {
                        return $this->processLinkUser($account);
                    } else {
                        return $this->renderError(
                            \Yii::t("app",
                                'The external account ("{0}") you just authenticated with is ' .
                                'not configured to allow account linking on this Phabricator ' .
                                'install. An administrator may have recently disabled it.', [
                                    $provider->getProviderName()
                                ]));
                    }
                }
            }
        }

// This should be unreachable, but fail explicitly if we get here somehow.
        return new Aphront400Response();
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @return AphrontResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \AphrontQueryException
     * @throws Exception
     * @author 陈妙威
     */
    private function processLoginUser(PhabricatorExternalAccount $account)
    {
        $user = PhabricatorUser::find()->andWhere(['phid' => $account->getUserPHID()])->one();
        if (!$user) {
            return $this->renderError(
                \Yii::t("app",
                    'The external account you just logged in with is not associated ' .
                    'with a valid Phabricator user.'));
        }

        return $this->loginUser($user);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function processRegisterUser(PhabricatorExternalAccount $account)
    {
        $account_secret = $account->getAccountSecret();
        $register_uri = $this->getApplicationURI('index/register', ['akey' => $account_secret]);
        return $this->setAccountKeyAndContinue($account, $register_uri);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function processLinkUser(PhabricatorExternalAccount $account)
    {
        $account_secret = $account->getAccountSecret();
        $confirm_uri = $this->getApplicationURI('confirmlink/' . $account_secret . '/');
        return $this->setAccountKeyAndContinue($account, $confirm_uri);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @param $next_uri
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \AphrontQueryException
     */
    private function setAccountKeyAndContinue(
        PhabricatorExternalAccount $account,
        $next_uri)
    {

        if ($account->getUserPHID()) {
            throw new Exception(\Yii::t("app", 'Account is already registered or linked.'));
        }

        // Regenerate the registration secret key, set it on the external account,
        // set a cookie on the user's machine, and redirect them to registration.
        // See PhabricatorAuthRegisterController for discussion of the registration
        // key.

        $registration_key = Filesystem::readRandomCharacters(32);
        $account->setProperty(
            'registrationKey',
            PhabricatorHash::weakDigest($registration_key));

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $account->save();
        unset($unguarded);

        $this->getRequest()->setTemporaryCookie(
            PhabricatorCookies::COOKIE_REGISTRATION,
            $registration_key);

        return (new AphrontRedirectResponse())->setURI($next_uri);
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|null
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    private function loadProvider()
    {
        $provider = PhabricatorAuthProvider::getEnabledProviderByKey($this->providerKey);

        if (!$provider) {
            return $this->renderError(
                \Yii::t("app",
                    'The account you are attempting to log in with uses a nonexistent ' .
                    'or disabled authentication provider (with key "{0}"). An ' .
                    'administrator may have recently disabled this provider.', [
                        $this->providerKey
                    ]));
        }

        $this->provider = $provider;

        return null;
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
            \Yii::t("app", 'Login Failed'),
            array($message));
    }

    /**
     * @param PhabricatorAuthProvider $provider
     * @param $content
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    public function buildProviderPageResponse(PhabricatorAuthProvider $provider, $content)
    {

        $crumbs = $this->buildApplicationCrumbs();

        if ($this->getRequest()->getViewer()->isLoggedIn()) {
            $crumbs->addTextCrumb(\Yii::t("app", 'Link Account'), $provider->getSettingsURI());
        } else {
            $crumbs->addTextCrumb(\Yii::t("app", 'Log In'), $this->getApplicationURI('start/'));
        }

        $crumbs->addTextCrumb($provider->getProviderName());
        $crumbs->setBorder(true);

        return $this->newPage()
            ->addContentClass("d-flex justify-content-center align-items-center bg-white")
            ->setTitle(\Yii::t("app", 'Log In'))
//            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @param PhabricatorAuthProvider $provider
     * @param $message
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws Exception
     * @author 陈妙威
     */
    public function buildProviderErrorResponse(
        PhabricatorAuthProvider $provider,
        $message)
    {

        $message = \Yii::t("app",
            'Authentication provider ("{0}") encountered an error while attempting ' .
            'to log in. {1}', [
                $provider->getProviderName(), $message
            ]);

        return $this->renderError($message);
    }

}
