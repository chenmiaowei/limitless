<?php

namespace orangins\modules\auth\provider;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use PhutilAuthAdapter;
use PhutilAuthUserAbortedException;
use PhutilOAuth1AuthAdapter;
use PhutilOAuthAuthAdapter;
use PhutilOpaqueEnvelope;

/**
 * Class PhabricatorOAuth1AuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
abstract class PhabricatorOAuth1AuthProvider
    extends PhabricatorOAuthAuthProvider
{

    /**
     * @var
     */
    protected $adapter;

    /**
     *
     */
    const PROPERTY_CONSUMER_KEY = 'oauth1:consumer:key';
    /**
     *
     */
    const PROPERTY_CONSUMER_SECRET = 'oauth1:consumer:secret';
    /**
     *
     */
    const PROPERTY_PRIVATE_KEY = 'oauth1:private:key';

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getIDKey()
    {
        return self::PROPERTY_CONSUMER_KEY;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getSecretKey()
    {
        return self::PROPERTY_CONSUMER_SECRET;
    }

    /**
     * @param PhutilAuthAdapter|PhutilOAuth1AuthAdapter $adapter
     * @return PhutilAuthAdapter|PhutilOAuth1AuthAdapter|PhutilOAuthAuthAdapter
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function configureAdapter(PhutilAuthAdapter $adapter)
    {
        assert_instances_of([$adapter], PhutilOAuth1AuthAdapter::class);

        $config = $this->getProviderConfig();
        $adapter->setConsumerKey($config->getProperty(self::PROPERTY_CONSUMER_KEY));
        $secret = $config->getProperty(self::PROPERTY_CONSUMER_SECRET);
        if (strlen($secret)) {
            $adapter->setConsumerSecret(new PhutilOpaqueEnvelope($secret));
        }
        $adapter->setCallbackURI(PhabricatorEnv::getURI($this->getLoginURI()));
        return $adapter;
    }

    /**
     * @param AphrontRequest $request
     * @param $mode
     * @return wild
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderLoginForm(AphrontRequest $request, $mode)
    {
        $attributes = array(
            'method' => 'POST',
            'uri' => $this->getLoginURI(),
        );
        return $this->renderStandardLoginButton($request, $mode, $attributes);
    }

    /**
     * @param PhabricatorAuthLoginAction $action
     * @return array|mixed
     * @throws \yii\base\Exception
     * @throws \AphrontQueryException
     * @throws PhutilAuthUserAbortedException
     * @throws \Exception
     * @throws \Throwable
     * @author 陈妙威
     */
    public function processLoginRequest(
        PhabricatorAuthLoginAction $action)
    {

        $request = $action->getRequest();
        $adapter = $this->getAdapter();
        $account = null;
        $response = null;

        if ($request->isHTTPPost()) {
            // Add a CSRF code to the callback URI, which we'll verify when
            // performing the login.

            $client_code = $this->getAuthCSRFCode($request);

            $callback_uri = $adapter->getCallbackURI();
            $callback_uri = $callback_uri . $client_code . '/';
            $adapter->setCallbackURI($callback_uri);

            $uri = $adapter->getClientRedirectURI();

            $this->saveHandshakeTokenSecret(
                $client_code,
                $adapter->getTokenSecret());

            $response = (new AphrontRedirectResponse())
                ->setIsExternal(true)
                ->setURI($uri);
            return array($account, $response);
        }

        $denied = $request->getStr('denied');
        if (strlen($denied)) {
            // Twitter indicates that the user cancelled the login attempt by
            // returning "denied" as a parameter.
            throw new PhutilAuthUserAbortedException();
        }

        // NOTE: You can get here via GET, this should probably be a bit more
        // user friendly.

        $this->verifyAuthCSRFCode($request, $action->getExtraURIData());

        $token = $request->getStr('oauth_token');
        $verifier = $request->getStr('oauth_verifier');

        if (!$token) {
            throw new Exception(\Yii::t("app", "Expected '%s' in request!", 'oauth_token'));
        }

        if (!$verifier) {
            throw new Exception(\Yii::t("app", "Expected '%s' in request!", 'oauth_verifier'));
        }

        $adapter->setToken($token);
        $adapter->setVerifier($verifier);

        $client_code = $this->getAuthCSRFCode($request);
        $token_secret = $this->loadHandshakeTokenSecret($client_code);
        $adapter->setTokenSecret($token_secret);

        // NOTE: As a side effect, this will cause the OAuth adapter to request
        // an access token.

        try {
            $account_id = $adapter->getAccountID();
        } catch (Exception $ex) {
            // TODO: Handle this in a more user-friendly way.
            throw $ex;
        }

        if (!strlen($account_id)) {
            $response = $action->buildProviderErrorResponse(
                $this,
                \Yii::t("app",
                    'The OAuth provider failed to retrieve an account ID.'));

            return array($account, $response);
        }

        return array($this->loadOrCreateAccount($account_id), $response);
    }

    /**
     * @param AphrontRequest $request
     * @param array $values
     * @return array
     * @author 陈妙威
     */
    public function processEditForm(
        AphrontRequest $request,
        array $values)
    {

        $key_ckey = self::PROPERTY_CONSUMER_KEY;
        $key_csecret = self::PROPERTY_CONSUMER_SECRET;

        return $this->processOAuthEditForm(
            $request,
            $values,
            \Yii::t("app", 'Consumer key is required.'),
            \Yii::t("app", 'Consumer secret is required.'));
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function extendEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues)
    {

        return $this->extendOAuthEditForm(
            $request,
            $form,
            $values,
            $issues,
            \Yii::t("app", 'OAuth Consumer Key'),
            \Yii::t("app", 'OAuth Consumer Secret'));
    }

    /**
     * @param PhabricatorAuthProviderConfigTransaction $xaction
     * @return null|string
     * @throws \PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function renderConfigPropertyTransactionTitle(
        PhabricatorAuthProviderConfigTransaction $xaction)
    {

        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();
        $key = $xaction->getMetadataValue(
            PhabricatorAuthProviderConfigTransaction::PROPERTY_KEY);

        switch ($key) {
            case self::PROPERTY_CONSUMER_KEY:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth consumer key for this provider from ' .
                        '"{1}" to "{2}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $old,
                            $new
                        ]);
                } else {
                    return \Yii::t("app",
                        '{1} set the OAuth consumer key for this provider to ' .
                        '"{2}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $new
                        ]);
                }
            case self::PROPERTY_CONSUMER_SECRET:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '%s updated the OAuth consumer secret for this provider.',
                        $xaction->renderHandleLink($author_phid));
                } else {
                    return \Yii::t("app",
                        '%s set the OAuth consumer secret for this provider.',
                        $xaction->renderHandleLink($author_phid));
                }
        }

        return parent::renderConfigPropertyTransactionTitle($xaction);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @throws Exception
     * @author 陈妙威
     */
    protected function synchronizeOAuthAccount(
        PhabricatorExternalAccount $account)
    {
        $adapter = $this->getAdapter();

        $oauth_token = $adapter->getToken();
        $oauth_token_secret = $adapter->getTokenSecret();

        $account->setProperty('oauth1.token', $oauth_token);
        $account->setProperty('oauth1.token.secret', $oauth_token_secret);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PHUIObjectItemView $item
     * @param PhabricatorExternalAccount $account
     * @throws Exception
     * @author 陈妙威
     */
    public function willRenderLinkedAccount(
        PhabricatorUser $viewer,
        PHUIObjectItemView $item,
        PhabricatorExternalAccount $account)
    {

        $item->addAttribute(\Yii::t("app", 'OAuth1 Account'));

        parent::willRenderLinkedAccount($viewer, $item, $account);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getContentSecurityPolicyFormActions()
    {
        return $this->getAdapter()->getContentSecurityPolicyFormActions();
    }

    /* -(  Temporary Secrets  )-------------------------------------------------- */


    /**
     * @param $client_code
     * @param $secret
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    private function saveHandshakeTokenSecret($client_code, $secret)
    {
        $secret_type = PhabricatorOAuth1SecretTemporaryTokenType::TOKENTYPE;
        $key = $this->getHandshakeTokenKeyFromClientCode($client_code);
        $type = $this->getTemporaryTokenType($secret_type);

        // Wipe out an existing token, if one exists.
        $token = PhabricatorAuthTemporaryToken::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withTokenResources(array($key))
            ->withTokenTypes(array($type))
            ->executeOne();
        if ($token) {
            $token->delete();
        }

        // Save the new secret.
        (new PhabricatorAuthTemporaryToken())
            ->setTokenResource($key)
            ->setTokenType($type)
            ->setTokenExpires(time() + phutil_units('1 hour in seconds'))
            ->setTokenCode($secret)
            ->save();
    }

    /**
     * @param $client_code
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function loadHandshakeTokenSecret($client_code)
    {
        $secret_type = PhabricatorOAuth1SecretTemporaryTokenType::TOKENTYPE;
        $key = $this->getHandshakeTokenKeyFromClientCode($client_code);
        $type = $this->getTemporaryTokenType($secret_type);

        $token = PhabricatorAuthTemporaryToken::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withTokenResources(array($key))
            ->withTokenTypes(array($type))
            ->withExpired(false)
            ->executeOne();

        if (!$token) {
            throw new Exception(
                \Yii::t("app",
                    'Unable to load your OAuth1 token secret from storage. It may ' .
                    'have expired. Try authenticating again.'));
        }

        return $token->getTokenCode();
    }

    /**
     * @param $core_type
     * @return string
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getTemporaryTokenType($core_type)
    {
        // Namespace the type so that multiple providers don't step on each
        // others' toes if a user starts Mediawiki and Bitbucket auth at the
        // same time.

        // TODO: This isn't really a proper use of the table and should get
        // cleaned up some day: the type should be constant.

        return $core_type . ':' . $this->getProviderConfig()->getID();
    }

    /**
     * @param $client_code
     * @return string
     * @author 陈妙威
     */
    private function getHandshakeTokenKeyFromClientCode($client_code)
    {
        // NOTE: This is very slightly coercive since the TemporaryToken table
        // expects an "objectPHID" as an identifier, but nothing about the storage
        // is bound to PHIDs.

        return 'oauth1:secret/' . $client_code;
    }

}
