<?php

namespace orangins\modules\auth\provider;

use AphrontWriteGuard;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\exception\AphrontMalformedRequestException;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\infrastructure\util\PhabricatorSlug;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\auth\actions\PhabricatorAuthLinkAction;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\actions\PhabricatorAuthStartAction;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\policy\constants\PhabricatorPolicies;
use PhutilAuthAdapter;
use orangins\modules\auth\view\PhabricatorAuthAccountView;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\PhabricatorApplication;
use orangins\lib\OranginsObject;
use PhutilMethodNotImplementedException;
use PhutilInvalidStateException;
use PhutilClassMapQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use PhutilTypeSpec;
use PhutilURI;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
abstract class PhabricatorAuthProvider extends OranginsObject
{

    /**
     * @var
     */
    private $providerConfig;

    /**
     * @param PhabricatorAuthProviderConfig $config
     * @return $this
     * @author 陈妙威
     */
    public function attachProviderConfig(PhabricatorAuthProviderConfig $config)
    {
        $this->providerConfig = $config;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasProviderConfig()
    {
        return (bool)$this->providerConfig;
    }

    /**
     * @return PhabricatorAuthProviderConfig
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function getProviderConfig()
    {
        if ($this->providerConfig === null) {
            throw new PhutilInvalidStateException('attachProviderConfig');
        }
        return $this->providerConfig;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConfigurationHelp()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \ReflectionException
     */
    public function getDefaultProviderConfig()
    {
        return (new PhabricatorAuthProviderConfig())
            ->setProviderClass($this->getClassShortName())
            ->setIsEnabled(1)
            ->setShouldAllowLogin(1)
            ->setShouldAllowRegistration(1)
            ->setShouldAllowLink(1)
            ->setShouldAllowUnlink(1);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNameForCreate()
    {
        return $this->getProviderName();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getDescriptionForCreate()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProviderKey()
    {
        return $this->getAdapter()->getAdapterKey();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProviderType()
    {
        return $this->getAdapter()->getAdapterType();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProviderDomain()
    {
        return $this->getAdapter()->getAdapterDomain();
    }

    /**
     * @return PhabricatorAuthProvider[]
     * @author 陈妙威
     */
    public static function getAllBaseProviders()
    {
        return (new PhutilClassMapQuery())
            ->setUniqueMethod("getClassShortName")
            ->setAncestorClass(PhabricatorAuthProvider::class)
            ->execute();
    }

    /**
     * @return array|null
     * @throws PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public static function getAllProviders()
    {
        static $providers;

        if ($providers === null) {
            $objects = self::getAllBaseProviders();

            /** @var PhabricatorAuthProviderConfig[] $configs */
            $configs = PhabricatorAuthProviderConfig::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->execute();

            $providers = array();
            foreach ($configs as $config) {
                $providerClass = $config->getProviderClass();
                if (!isset($objects[$providerClass])) {
                    // This configuration is for a provider which is not installed.
                    continue;
                }

                $object = clone $objects[$config->getProviderClass()];
                $object->attachProviderConfig($config);

                $key = $object->getProviderKey();
                if (isset($providers[$key])) {
                    throw new Exception(
                        \Yii::t("app",
                            "Two authentication providers use the same provider key " .
                            "('{0}'). Each provider must be identified by a unique key.", [
                                $key
                            ]));
                }
                $providers[$key] = $object;
            }
        }

        return $providers;
    }

    /**
     * @return PhabricatorAuthProvider[]
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    public static function getAllEnabledProviders()
    {
        $providers = self::getAllProviders();
        foreach ($providers as $key => $provider) {
            if (!$provider->isEnabled()) {
                unset($providers[$key]);
            }
        }
        return $providers;
    }

    /**
     * @param $provider_key
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    public static function getEnabledProviderByKey($provider_key)
    {
        return ArrayHelper::getValue(self::getAllEnabledProviders(), $provider_key);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getProviderName();

    /**
     * @return PhutilAuthAdapter
     * @author 陈妙威
     */
    abstract public function getAdapter();

    /**
     * @return bool
     * @author 陈妙威
     */
    public function autoRegister()
    {
        return false;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function isEnabled()
    {
        return $this->getProviderConfig()->getIsEnabled();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function shouldAllowLogin()
    {
        return $this->getProviderConfig()->getShouldAllowLogin();
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function shouldAllowRegistration()
    {
        if (!$this->shouldAllowLogin()) {
            return false;
        }

        return $this->getProviderConfig()->getShouldAllowRegistration();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function shouldAllowAccountLink()
    {
        return $this->getProviderConfig()->getShouldAllowLink();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function shouldAllowAccountUnlink()
    {
        return $this->getProviderConfig()->getShouldAllowUnlink();
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function should_trust_emails()
    {
        return $this->shouldAllowEmailTrustConfiguration() &&
            $this->getProviderConfig()->getShouldTrustEmails();
    }

    /**
     * Should we allow the adapter to be marked as "trusted". This is true for
     * all adapters except those that allow the user to type in emails (see
     * @{class:PhabricatorPasswordAuthProvider}).
     */
    public function shouldAllowEmailTrustConfiguration()
    {
        return true;
    }

    /**
     * @param PhabricatorAuthStartAction $controller
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildLoginForm(PhabricatorAuthStartAction $controller)
    {
        return $this->renderLoginForm($controller->getRequest(), $mode = 'start');
    }

    /**
     * @param PhabricatorAuthStartAction $controller
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildInviteForm(PhabricatorAuthStartAction $controller)
    {
        return $this->renderLoginForm($controller->getRequest(), $mode = 'invite');
    }

    /**
     * @param PhabricatorAuthLoginAction $action
     * @return mixed
     * @author 陈妙威
     */
    abstract public function processLoginRequest(PhabricatorAuthLoginAction $action);

    /**
     * @param PhabricatorAuthLinkAction $controller
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildLinkForm(PhabricatorAuthLinkAction $controller)
    {
        return $this->renderLoginForm($controller->getRequest(), $mode = 'link');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowAccountRefresh()
    {
        return true;
    }

    /**
     * @param PhabricatorAuthLinkAction $controller
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildRefreshForm(
        PhabricatorAuthLinkAction $controller)
    {
        return $this->renderLoginForm($controller->getRequest(), $mode = 'refresh');
    }

    /**
     * @param AphrontRequest $request
     * @param $mode
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    protected function renderLoginForm(AphrontRequest $request, $mode)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function createProviders()
    {
        return array($this);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    protected function willSaveAccount(PhabricatorExternalAccount $account)
    {
        return;
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    public function willRegisterAccount(PhabricatorExternalAccount $account)
    {
        return;
    }

    /**
     * @return PhabricatorExternalAccount
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newExternalAccount() {
        $config = $this->getProviderConfig();
        $adapter = $this->getAdapter();

        return (new PhabricatorExternalAccount())
            ->setAccountType($adapter->getAdapterType())
            ->setAccountDomain($adapter->getAdapterDomain())
            ->setProviderConfigPHID($config->getPHID());
    }

    /**
     * @param $account_id
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \Throwable
     */
    public function loadOrCreateAccount($account_id)
    {
        if (!strlen($account_id)) {
            throw new Exception(\Yii::t("app", 'Empty account ID!'));
        }

        $adapter = $this->getAdapter();
        $adapter_class = get_class($adapter);

        if (!strlen($adapter->getAdapterType())) {
            throw new Exception(
                \Yii::t("app",
                    "AuthAdapter (of class '{0}') has an invalid implementation: " .
                    "no adapter type.", [
                        $adapter_class
                    ]));
        }

        if (!strlen($adapter->getAdapterDomain())) {
            throw new Exception(
                \Yii::t("app",
                    "AuthAdapter (of class '{0}') has an invalid implementation: " .
                    "no adapter domain.", [
                        $adapter_class
                    ]));
        }

        $account = PhabricatorExternalAccount::find()
            ->andWhere([
                'account_type' => $adapter->getAdapterType(),
                'account_domain' => $adapter->getAdapterDomain(),
                'account_id' => $account_id
            ])->one();
        if (!$account) {
            $account = $this->newExternalAccount()
                ->setAccountID($account_id);
        }

        $account->setUsername($adapter->getAccountName());
        $account->setRealName($adapter->getAccountRealName());
        $account->setEmail($adapter->getAccountEmail());
        $account->setAccountURI($adapter->getAccountURI());

        $account->setProfileImagePHID(null);
        $image_uri = $adapter->getAccountImageURI();
        if ($image_uri) {
            try {
                $name = PhabricatorSlug::normalize($this->getProviderName());
                $name = $name . '-profile.jpg';

                // TODO: If the image has not changed, we do not need to make a new
                // file entry for it, but there's no convenient way to do this with
                // PhabricatorFile right now. The storage will get shared, so the impact
                // here is negligible.
                $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                $image_file = PhabricatorFile::newFromFileDownload(
                    $image_uri,
                    array(
                        'name' => $name,
                        'viewPolicy' => PhabricatorPolicies::POLICY_NOONE,
                    ));
                if ($image_file->isViewableImage()) {
                    $image_file
                        ->setViewPolicy(PhabricatorPolicies::getMostOpenPolicy())
                        ->setCanCDN(true)
                        ->save();
                    $account->setProfileImagePHID($image_file->getPHID());
                } else {
                    $image_file->delete();
                }
                unset($unguarded);

            } catch (Exception $ex) {
                // Log this but proceed, it's not especially important that we
                // be able to pull profile images.
                \Yii::error($ex);
            }
        }

        $this->willSaveAccount($account);

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        if (!$account->save()) {
            throw new ActiveRecordException("Create account error. ", $account->getErrorSummary(true));
        }
        unset($unguarded);
        return $account;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getLoginURI()
    {
        /** @var PhabricatorApplication $app */
        $app = PhabricatorApplication::getByClass(PhabricatorAuthApplication::className());
        $applicationURI = $app->getApplicationURI('index/login', [
            'pkey' => $this->getProviderKey(),
        ]);
        return $applicationURI;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSettingsURI()
    {
        return '/settings/panel/external/';
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getStartURI()
    {
        $app = PhabricatorApplication::getByClass(PhabricatorAuthApplication::className());
        $uri = $app->getApplicationURI('/start/');
        return $uri;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDefaultRegistrationProvider()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireRegistrationPassword()
    {
        return false;
    }

    /**
     * @author 陈妙威
     * @return PhabricatorExternalAccount
     * @throws PhutilInvalidStateException
     */
    public function getDefaultExternalAccount()
    {
        return $this->newExternalAccount();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLoginOrder()
    {
        return '500-' . $this->getProviderName();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Generic';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLoginFormAButton()
    {
        return false;
    }

    /**
     * @param PhabricatorAuthProviderConfigTransaction $xaction
     * @return null
     * @author 陈妙威
     */
    public function renderConfigPropertyTransactionTitle(
        PhabricatorAuthProviderConfigTransaction $xaction)
    {

        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function readFormValuesFromProvider()
    {
        return array();
    }

    /**
     * @param AphrontRequest $request
     * @return array
     * @author 陈妙威
     */
    public function readFormValuesFromRequest(AphrontRequest $request)
    {
        return array();
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

        $errors = array();
        $issues = array();

        return array($errors, $issues, $values);
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @author 陈妙威
     */
    public function extendEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues)
    {

        return;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PHUIObjectItemView $item
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     * @throws Exception
     */
    public function willRenderLinkedAccount(
        PhabricatorUser $viewer,
        PHUIObjectItemView $item,
        PhabricatorExternalAccount $account)
    {

        $account_view = (new PhabricatorAuthAccountView())
            ->setExternalAccount($account)
            ->setAuthProvider($this);

        $item->appendChild(
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'mmr mml mst mmb',
                ),
                $account_view));
    }

    /**
     * Return true to use a two-step configuration (setup, configure) instead of
     * the default single-step configuration. In practice, this means that
     * creating a new provider instance will redirect back to the edit page
     * instead of the provider list.
     *
     * @return bool True if this provider uses two-step configuration.
     */
    public function hasSetupStep()
    {
        return false;
    }

    /**
     * Render a standard login/register button element.
     *
     * The `$attributes` parameter takes these keys:
     *
     *   - `uri`: URI the button should take the user to when clicked.
     *   - `method`: Optional HTTP method the button should use, defaults to GET.
     *
     * @param AphrontRequest $request
     * @param   AphrontRequest  HTTP request.
     * @param array $attributes
     * @return  wild            Log in button.
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \Exception
     */
    protected function renderStandardLoginButton(
        AphrontRequest $request,
        $mode,
        array $attributes = array())
    {

        PhutilTypeSpec::checkMap(
            $attributes,
            array(
                'method' => 'optional string',
                'uri' => 'string',
                'sigil' => 'optional string',
            ));

        $viewer = $request->getViewer();
        $adapter = $this->getAdapter();

        if ($mode == 'link') {
            $button_text = \Yii::t("app", 'Link External Account');
        } else if ($mode == 'refresh') {
            $button_text = \Yii::t("app", 'Refresh Account Link');
        } else if ($mode == 'invite') {
            $button_text = \Yii::t("app", 'Register Account');
        } else if ($this->shouldAllowRegistration()) {
            $button_text = \Yii::t("app", 'Log In or Register');
        } else {
            $button_text = \Yii::t("app", 'Log In');
        }

        $icon = (new PHUIIconView())
            ->setSpriteSheet(PHUIIconView::SPRITE_LOGIN)
            ->setSpriteIcon($this->getLoginIcon());

        $button = (new PHUIButtonView())
            ->setSize(PHUIButtonView::BIG)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setIcon($icon)
            ->setText($button_text)
            ->setSubtext($this->getProviderName());

        $uri = $attributes['uri'];
        $uri = new PhutilURI($uri);
        $params = $uri->getQueryParams();
        $uri->setQueryParams(array());

        $content = array($button);

        foreach ($params as $key => $value) {
            $content[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => $value,
                ));
        }

        $static_response = CelerityAPI::getStaticResourceResponse();
        $static_response->addContentSecurityPolicyURI('form-action', (string)$uri);

        foreach ($this->getContentSecurityPolicyFormActions() as $csp_uri) {
            $static_response->addContentSecurityPolicyURI('form-action', $csp_uri);
        }

        return JavelinHtml::phabricator_form(
            $viewer,
            array(
                'method' => ArrayHelper::getValue($attributes, 'method', 'GET'),
                'action' => (string)$uri,
                'sigil' => ArrayHelper::getValue($attributes, 'sigil'),
            ),
            $content);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function renderConfigurationFooter()
    {
        return null;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws AphrontMalformedRequestException
     * @throws Exception
     * @author 陈妙威
     */
    public function getAuthCSRFCode(AphrontRequest $request)
    {
        $phcid = $request->getCookie(PhabricatorCookies::COOKIE_CLIENTID);
        if (!strlen($phcid)) {
            throw new AphrontMalformedRequestException(
                \Yii::t("app", 'Missing Client ID Cookie'),
                \Yii::t("app",
                    'Your browser did not submit a "%s" cookie with client state ' .
                    'information in the request. Check that cookies are enabled. ' .
                    'If this problem persists, you may need to clear your cookies.',
                    PhabricatorCookies::COOKIE_CLIENTID),
                true);
        }

        return PhabricatorHash::weakDigest($phcid);
    }

    /**
     * @param AphrontRequest $request
     * @param $actual
     * @throws AphrontMalformedRequestException
     * @throws Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function verifyAuthCSRFCode(AphrontRequest $request, $actual)
    {
        $expect = $this->getAuthCSRFCode($request);

        if (!strlen($actual)) {
            throw new Exception(
                \Yii::t("app",
                    'The authentication provider did not return a client state ' .
                    'parameter in its response, but one was expected. If this ' .
                    'problem persists, you may need to clear your cookies.'));
        }

        if (!phutil_hashes_are_identical($actual, $expect)) {
            throw new Exception(
                \Yii::t("app",
                    'The authentication provider did not return the correct client ' .
                    'state parameter in its response. If this problem persists, you may ' .
                    'need to clear your cookies.'));
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsAutoLogin()
    {
        return false;
    }

    /**
     * @param AphrontRequest $request
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function getAutoLoginURI(AphrontRequest $request)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getContentSecurityPolicyFormActions()
    {
        return array();
    }

}
