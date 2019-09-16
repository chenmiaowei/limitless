<?php

namespace orangins\modules\auth\provider;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\people\models\PhabricatorExternalAccount;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorJIRAAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorJIRAAuthProvider extends PhabricatorOAuth1AuthProvider
{

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getJIRABaseURI()
    {
        return $this->getProviderConfig()->getProperty(self::PROPERTY_JIRA_URI);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'JIRA');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDescriptionForCreate()
    {
        return \Yii::t("app", 'Configure JIRA OAuth. NOTE: Only supports JIRA 6.');
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getConfigurationHelp()
    {
        return $this->getProviderConfigurationHelp();
    }

    /**
     * @return mixed|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        if ($this->isSetup()) {
            return \Yii::t("app",
                "**Step 1 of 2**: Provide the name and URI for your JIRA install.\n\n" .
                "In the next step, you will configure JIRA.");
        } else {
            $login_uri = PhabricatorEnv::getURI($this->getLoginURI());
            return \Yii::t("app",
                "**Step 2 of 2**: In this step, you will configure JIRA.\n\n" .
                "**Create a JIRA Application**: Log into JIRA and go to " .
                "**Administration**, then **Add-ons**, then **Application Links**. " .
                "Click the button labeled **Add Application Link**, and use these " .
                "settings to create an application:\n\n" .
                "  - **Server URL**: `%s`\n" .
                "  - Then, click **Next**. On the second page:\n" .
                "  - **Application Name**: `Phabricator`\n" .
                "  - **Application Type**: `Generic Application`\n" .
                "  - Then, click **Create**.\n\n" .
                "**Configure Your Application**: Find the application you just " .
                "created in the table, and click the **Configure** link under " .
                "**Actions**. Select **Incoming Authentication** and click the " .
                "**OAuth** tab (it may be selected by default). Then, use these " .
                "settings:\n\n" .
                "  - **Consumer Key**: Set this to the \"Consumer Key\" value in the " .
                "form above.\n" .
                "  - **Consumer Name**: `Phabricator`\n" .
                "  - **Public Key**: Set this to the \"Public Key\" value in the " .
                "form above.\n" .
                "  - **Consumer Callback URL**: `%s`\n" .
                "Click **Save** in JIRA. Authentication should now be configured, " .
                "and this provider should work correctly.",
                PhabricatorEnv::getProductionURI('/'),
                $login_uri);
        }
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        $config = $this->getProviderConfig();

        return (new PhutilJIRAAuthAdapter())
            ->setAdapterDomain($config->getProviderDomain())
            ->setJIRABaseURI($config->getProperty(self::PROPERTY_JIRA_URI))
            ->setPrivateKey(
                new PhutilOpaqueEnvelope(
                    $config->getProperty(self::PROPERTY_PRIVATE_KEY)));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Jira';
    }

    /**
     * @return bool
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function isSetup()
    {
        return !$this->getProviderConfig()->getID();
    }

    /**
     *
     */
    const PROPERTY_JIRA_NAME = 'oauth1:jira:name';
    /**
     *
     */
    const PROPERTY_JIRA_URI = 'oauth1:jira:uri';
    /**
     *
     */
    const PROPERTY_PUBLIC_KEY = 'oauth1:jira:key:public';
    /**
     *
     */
    const PROPERTY_PRIVATE_KEY = 'oauth1:jira:key:private';
    /**
     *
     */
    const PROPERTY_REPORT_LINK = 'oauth1:jira:report:link';
    /**
     *
     */
    const PROPERTY_REPORT_COMMENT = 'oauth1:jira:report:comment';


    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function readFormValuesFromProvider()
    {
        $config = $this->getProviderConfig();
        $uri = $config->getProperty(self::PROPERTY_JIRA_URI);

        return array(
            self::PROPERTY_JIRA_NAME => $this->getProviderDomain(),
            self::PROPERTY_JIRA_URI => $uri,
        );
    }

    /**
     * @param AphrontRequest $request
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function readFormValuesFromRequest(AphrontRequest $request)
    {
        $is_setup = $this->isSetup();
        if ($is_setup) {
            $name = $request->getStr(self::PROPERTY_JIRA_NAME);
        } else {
            $name = $this->getProviderDomain();
        }

        return array(
            self::PROPERTY_JIRA_NAME => $name,
            self::PROPERTY_JIRA_URI => $request->getStr(self::PROPERTY_JIRA_URI),
            self::PROPERTY_REPORT_LINK =>
                $request->getInt(self::PROPERTY_REPORT_LINK, 0),
            self::PROPERTY_REPORT_COMMENT =>
                $request->getInt(self::PROPERTY_REPORT_COMMENT, 0),
        );
    }

    /**
     * @param AphrontRequest $request
     * @param array $values
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function processEditForm(
        AphrontRequest $request,
        array $values)
    {
        $errors = array();
        $issues = array();

        $is_setup = $this->isSetup();

        $key_name = self::PROPERTY_JIRA_NAME;
        $key_uri = self::PROPERTY_JIRA_URI;

        if (!strlen($values[$key_name])) {
            $errors[] = \Yii::t("app", 'JIRA instance name is required.');
            $issues[$key_name] = \Yii::t("app", 'Required');
        } else if (!preg_match('/^[a-z0-9.]+\z/', $values[$key_name])) {
            $errors[] = \Yii::t("app",
                'JIRA instance name must contain only lowercase letters, digits, and ' .
                'period.');
            $issues[$key_name] = \Yii::t("app", 'Invalid');
        }

        if (!strlen($values[$key_uri])) {
            $errors[] = \Yii::t("app", 'JIRA base URI is required.');
            $issues[$key_uri] = \Yii::t("app", 'Required');
        } else {
            $uri = new PhutilURI($values[$key_uri]);
            if (!$uri->getProtocol()) {
                $errors[] = \Yii::t("app",
                    'JIRA base URI should include protocol (like "https://").');
                $issues[$key_uri] = \Yii::t("app", 'Invalid');
            }
        }

        if (!$errors && $is_setup) {
            $config = $this->getProviderConfig();

            $config->setProviderDomain($values[$key_name]);

            $consumer_key = 'phjira.' . Filesystem::readRandomCharacters(16);
            list($public, $private) = PhutilJIRAAuthAdapter::newJIRAKeypair();

            $config->setProperty(self::PROPERTY_PUBLIC_KEY, $public);
            $config->setProperty(self::PROPERTY_PRIVATE_KEY, $private);
            $config->setProperty(self::PROPERTY_CONSUMER_KEY, $consumer_key);
        }

        return array($errors, $issues, $values);
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function extendEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues)
    {

        if (!function_exists('openssl_pkey_new')) {
            // TODO: This could be a bit prettier.
            throw new Exception(
                \Yii::t("app",
                    "The PHP 'openssl' extension is not installed. You must install " .
                    "this extension in order to add a JIRA authentication provider, " .
                    "because JIRA OAuth requests use the RSA-SHA1 signing algorithm. " .
                    "Install the 'openssl' extension, restart Phabricator, and try " .
                    "again."));
        }

        $form->appendRemarkupInstructions(
            \Yii::t("app",
                'NOTE: This provider **only supports JIRA 6**. It will not work with ' .
                'JIRA 5 or earlier.'));

        $is_setup = $this->isSetup();
        $viewer = $request->getViewer();

        $e_required = $request->isFormPost() ? null : true;

        $v_name = $values[self::PROPERTY_JIRA_NAME];
        if ($is_setup) {
            $e_name = ArrayHelper::getValue($issues, self::PROPERTY_JIRA_NAME, $e_required);
        } else {
            $e_name = null;
        }

        $v_uri = $values[self::PROPERTY_JIRA_URI];
        $e_uri = ArrayHelper::getValue($issues, self::PROPERTY_JIRA_URI, $e_required);

        if ($is_setup) {
            $form
                ->appendRemarkupInstructions(
                    \Yii::t("app",
                        "**JIRA Instance Name**\n\n" .
                        "Choose a permanent name for this instance of JIRA. Phabricator " .
                        "uses this name internally to keep track of this instance of " .
                        "JIRA, in case the URL changes later.\n\n" .
                        "Use lowercase letters, digits, and period. For example, " .
                        "`jira`, `jira.mycompany` or `jira.engineering` are reasonable " .
                        "names."))
                ->appendChild(
                    (new AphrontFormTextControl())
                        ->setLabel(\Yii::t("app", 'JIRA Instance Name'))
                        ->setValue($v_name)
                        ->setName(self::PROPERTY_JIRA_NAME)
                        ->setError($e_name));
        } else {
            $form
                ->appendChild(
                    (new AphrontFormStaticControl())
                        ->setLabel(\Yii::t("app", 'JIRA Instance Name'))
                        ->setValue($v_name));
        }

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'JIRA Base URI'))
                    ->setValue($v_uri)
                    ->setName(self::PROPERTY_JIRA_URI)
                    ->setCaption(
                        \Yii::t("app",
                            'The URI where JIRA is installed. For example: %s',
                            phutil_tag('tt', array(), 'https://jira.mycompany.com/')))
                    ->setError($e_uri));

        if (!$is_setup) {
            $config = $this->getProviderConfig();


            $ckey = $config->getProperty(self::PROPERTY_CONSUMER_KEY);
            $ckey = phutil_tag('tt', array(), $ckey);

            $pkey = $config->getProperty(self::PROPERTY_PUBLIC_KEY);
            $pkey = phutil_escape_html_newlines($pkey);
            $pkey = phutil_tag('tt', array(), $pkey);

            $form
                ->appendRemarkupInstructions(
                    \Yii::t("app",
                        'NOTE: **To complete setup**, copy and paste these keys into JIRA ' .
                        'according to the instructions below.'))
                ->appendChild(
                    (new AphrontFormStaticControl())
                        ->setLabel(\Yii::t("app", 'Consumer Key'))
                        ->setValue($ckey))
                ->appendChild(
                    (new AphrontFormStaticControl())
                        ->setLabel(\Yii::t("app", 'Public Key'))
                        ->setValue($pkey));

            $form
                ->appendRemarkupInstructions(
                    \Yii::t("app",
                        '= Integration Options = ' . "\n" .
                        'Configure how to record Revisions on JIRA tasks.' . "\n\n" .
                        'Note you\'ll have to restart the daemons for this to take ' .
                        'effect.'))
                ->appendChild(
                    (new AphrontFormCheckboxControl())
                        ->addCheckbox(
                            self::PROPERTY_REPORT_LINK,
                            1,
                            new PHUIRemarkupView(
                                $viewer,
                                \Yii::t("app",
                                    'Create **Issue Link** to the Revision, as an "implemented ' .
                                    'in" relationship.')),
                            $this->shouldCreateJIRALink()))
                ->appendChild(
                    (new AphrontFormCheckboxControl())
                        ->addCheckbox(
                            self::PROPERTY_REPORT_COMMENT,
                            1,
                            new PHUIRemarkupView(
                                $viewer,
                                \Yii::t("app",
                                    '**Post a comment** in the JIRA task, similar to the ' .
                                    'emails Phabricator sends.')),
                            $this->shouldCreateJIRAComment()));
        }

    }

    /**
     * JIRA uses a setup step to generate public/private keys.
     */
    public function hasSetupStep()
    {
        return true;
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function getJIRAProvider()
    {
        $providers = self::getAllEnabledProviders();

        foreach ($providers as $provider) {
            if ($provider instanceof PhabricatorJIRAAuthProvider) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @param $path
     * @param $method
     * @param array $params
     * @return mixed
     * @author 陈妙威
     */
    public function newJIRAFuture(
        PhabricatorExternalAccount $account,
        $path,
        $method,
        $params = array())
    {

        $adapter = clone $this->getAdapter();
        $adapter->setToken($account->getProperty('oauth1.token'));
        $adapter->setTokenSecret($account->getProperty('oauth1.token.secret'));

        return $adapter->newJIRAFuture($path, $method, $params);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function shouldCreateJIRALink()
    {
        $config = $this->getProviderConfig();
        return $config->getProperty(self::PROPERTY_REPORT_LINK, true);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function shouldCreateJIRAComment()
    {
        $config = $this->getProviderConfig();
        return $config->getProperty(self::PROPERTY_REPORT_COMMENT, true);
    }

}
