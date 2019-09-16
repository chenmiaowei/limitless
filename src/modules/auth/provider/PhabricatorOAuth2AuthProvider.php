<?php

namespace orangins\modules\auth\provider;

use AphrontWriteGuard;
use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use PhutilAuthAdapter;
use PhutilOAuth1AuthAdapter;
use PhutilOAuthAuthAdapter;
use PhutilOpaqueEnvelope;

/**
 * Class PhabricatorOAuth2AuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
abstract class PhabricatorOAuth2AuthProvider
    extends PhabricatorOAuthAuthProvider
{

    /**
     *
     */
    const PROPERTY_APP_ID = 'oauth:app:id';
    /**
     *
     */
    const PROPERTY_APP_SECRET = 'oauth:app:secret';

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getIDKey()
    {
        return self::PROPERTY_APP_ID;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getSecretKey()
    {
        return self::PROPERTY_APP_SECRET;
    }


    /**
     * @param PhutilAuthAdapter|PhutilOAuthAuthAdapter $adapter
     * @return PhutilAuthAdapter|PhutilOAuth1AuthAdapter|PhutilOAuthAuthAdapter
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function configureAdapter(PhutilAuthAdapter $adapter)
    {
        assert_instances_of([$adapter], PhutilOAuthAuthAdapter::class);

        $config = $this->getProviderConfig();
        $adapter->setClientID($config->getProperty(self::PROPERTY_APP_ID));
        $adapter->setClientSecret(
            new PhutilOpaqueEnvelope(
                $config->getProperty(self::PROPERTY_APP_SECRET)));
        $adapter->setRedirectURI(PhabricatorEnv::getURI($this->getLoginURI()));
        return $adapter;
    }

    /**
     * @param AphrontRequest $request
     * @param $mode
     * @return wild
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderLoginForm(AphrontRequest $request, $mode)
    {
        $adapter = $this->getAdapter();
        $adapter->setState($this->getAuthCSRFCode($request));

        $scope = $request->getStr('scope');
        if ($scope) {
            $adapter->setScope($scope);
        }

        $attributes = array(
            'method' => 'GET',
            'uri' => $adapter->getAuthenticateURI(),
        );

        return $this->renderStandardLoginButton($request, $mode, $attributes);
    }

    /**
     * @param PhabricatorAuthLoginAction $action
     * @return array|mixed
     * @throws \yii\base\Exception
     * @throws \AphrontQueryException
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

        $error = $request->getStr('error');
        if ($error) {
            $response = $action->buildProviderErrorResponse(
                $this,
                \Yii::t("app",
                    'The OAuth provider returned an error: %s',
                    $error));

            return array($account, $response);
        }

        $this->verifyAuthCSRFCode($request, $request->getStr('state'));

        $code = $request->getStr('code');
        if (!strlen($code)) {
            $response = $action->buildProviderErrorResponse(
                $this,
                \Yii::t("app",
                    'The OAuth provider did not return a "code" parameter in its ' .
                    'response.'));

            return array($account, $response);
        }

        $adapter->setCode($code);

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

        return $this->processOAuthEditForm(
            $request,
            $values,
            \Yii::t("app", 'Application ID is required.'),
            \Yii::t("app", 'Application secret is required.'));
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @author 陈妙威
     * @throws Exception
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
            \Yii::t("app", 'OAuth App ID'),
            \Yii::t("app", 'OAuth App Secret'));
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
            case self::PROPERTY_APP_ID:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application ID for this provider from ' .
                        '"{1}" to "{2}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $old,
                            $new
                        ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application ID for this provider to ' .
                        '"{1}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $new
                        ]);
                }
            case self::PROPERTY_APP_SECRET:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application secret for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application secret for this provider.', [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                }
            case self::PROPERTY_NOTE:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application notes for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application notes for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                }

        }

        return parent::renderConfigPropertyTransactionTitle($xaction);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     * @throws Exception
     */
    protected function synchronizeOAuthAccount(
        PhabricatorExternalAccount $account)
    {
        $adapter = $this->getAdapter();

        $oauth_token = $adapter->getAccessToken();
        $account->setProperty('oauth.token.access', $oauth_token);

        if ($adapter->supportsTokenRefresh()) {
            $refresh_token = $adapter->getRefreshToken();
            $account->setProperty('oauth.token.refresh', $refresh_token);
        } else {
            $account->setProperty('oauth.token.refresh', null);
        }

        $expires = $adapter->getAccessTokenExpires();
        $account->setProperty('oauth.token.access.expires', $expires);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @param bool $force_refresh
     * @return null
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function getOAuthAccessToken(
        PhabricatorExternalAccount $account,
        $force_refresh = false)
    {

        if ($account->getProviderKey() !== $this->getProviderKey()) {
            throw new Exception(\Yii::t("app", 'Account does not match provider!'));
        }

        if (!$force_refresh) {
            $access_expires = $account->getProperty('oauth.token.access.expires');
            $access_token = $account->getProperty('oauth.token.access');

            // Don't return a token with fewer than this many seconds remaining until
            // it expires.
            $shortest_token = 60;
            if ($access_token) {
                if ($access_expires === null ||
                    $access_expires > (time() + $shortest_token)) {
                    return $access_token;
                }
            }
        }

        $refresh_token = $account->getProperty('oauth.token.refresh');
        if ($refresh_token) {
            $adapter = $this->getAdapter();
            if ($adapter->supportsTokenRefresh()) {
                $adapter->refreshAccessToken($refresh_token);

                $this->synchronizeOAuthAccount($account);
                $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                $account->save();
                unset($unguarded);

                return $account->getProperty('oauth.token.access');
            }
        }

        return null;
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

        // Get a valid token, possibly refreshing it. If we're unable to refresh
        // it, render a message to that effect. The user may be able to repair the
        // link by manually reconnecting.

        $is_invalid = false;
        try {
            $oauth_token = $this->getOAuthAccessToken($account);
        } catch (Exception $ex) {
            $oauth_token = null;
            $is_invalid = true;
        }

        $item->addAttribute(\Yii::t("app", 'OAuth2 Account'));

        if ($oauth_token) {
            $oauth_expires = $account->getProperty('oauth.token.access.expires');
            if ($oauth_expires) {
                $item->addAttribute(
                    \Yii::t("app",
                        'Active OAuth Token (Expires: %s)',
                        OranginsViewUtil::phabricator_datetime($oauth_expires, $viewer)));
            } else {
                $item->addAttribute(
                    \Yii::t("app", 'Active OAuth Token'));
            }
        } else if ($is_invalid) {
            $item->addAttribute(\Yii::t("app", 'Invalid OAuth Access Token'));
        } else {
            $item->addAttribute(\Yii::t("app", 'No OAuth Access Token'));
        }

        parent::willRenderLinkedAccount($viewer, $item, $account);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsAutoLogin()
    {
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return
     * @throws \orangins\lib\exception\AphrontMalformedRequestException
     * @author 陈妙威
     */
    public function getAutoLoginURI(AphrontRequest $request)
    {
        $csrf_code = $this->getAuthCSRFCode($request);

        $adapter = $this->getAdapter();
        $adapter->setState($csrf_code);

        return $adapter->getAuthenticateURI();
    }

}
