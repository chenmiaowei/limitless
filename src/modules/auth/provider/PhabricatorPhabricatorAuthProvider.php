<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPhabricatorAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorPhabricatorAuthProvider
    extends PhabricatorOAuth2AuthProvider
{

    /**
     *
     */
    const PROPERTY_PHABRICATOR_NAME = 'oauth2:phabricator:name';
    /**
     *
     */
    const PROPERTY_PHABRICATOR_URI = 'oauth2:phabricator:uri';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Phabricator');
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     */
    public function getConfigurationHelp()
    {
        if ($this->isCreate()) {
            return \Yii::t("app",
                "**Step 1 of 2 - Name Phabricator OAuth Instance**\n\n" .
                'Choose a permanent name for the OAuth server instance of ' .
                'Phabricator. //This// instance of Phabricator uses this name ' .
                'internally to keep track of the OAuth server instance of ' .
                'Phabricator, in case the URL changes later.');
        }

        return parent::getConfigurationHelp();
    }

    /**
     * @return string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        $config = $this->getProviderConfig();
        $base_uri = rtrim(
            $config->getProperty(self::PROPERTY_PHABRICATOR_URI), '/');
        $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

        return \Yii::t("app",
            "**Step 2 of 2 - Configure Phabricator OAuth Instance**\n\n" .
            "To configure Phabricator OAuth, create a new application here:" .
            "\n\n" .
            "%s/oauthserver/client/create/" .
            "\n\n" .
            "When creating your application, use these settings:" .
            "\n\n" .
            "  - **Redirect URI:** Set this to: `%s`" .
            "\n\n" .
            "After completing configuration, copy the **Client ID** and " .
            "**Client Secret** to the fields above. (You may need to generate the " .
            "client secret by clicking 'New Secret' first.)",
            $base_uri,
            $login_uri);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        $config = $this->getProviderConfig();
        return (new PhutilPhabricatorAuthAdapter())
            ->setAdapterDomain($config->getProviderDomain())
            ->setPhabricatorBaseURI(
                $config->getProperty(self::PROPERTY_PHABRICATOR_URI));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Phabricator';
    }

    /**
     * @return bool
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function isCreate()
    {
        return !$this->getProviderConfig()->getID();
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function readFormValuesFromProvider()
    {
        $config = $this->getProviderConfig();
        $uri = $config->getProperty(self::PROPERTY_PHABRICATOR_URI);

        return parent::readFormValuesFromProvider() + array(
                self::PROPERTY_PHABRICATOR_NAME => $this->getProviderDomain(),
                self::PROPERTY_PHABRICATOR_URI => $uri,
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
        $is_setup = $this->isCreate();
        if ($is_setup) {
            $parent_values = array();
            $name = $request->getStr(self::PROPERTY_PHABRICATOR_NAME);
        } else {
            $parent_values = parent::readFormValuesFromRequest($request);
            $name = $this->getProviderDomain();
        }

        return $parent_values + array(
                self::PROPERTY_PHABRICATOR_NAME => $name,
                self::PROPERTY_PHABRICATOR_URI =>
                    $request->getStr(self::PROPERTY_PHABRICATOR_URI),
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

        $is_setup = $this->isCreate();

        if (!$is_setup) {
            list($errors, $issues, $values) =
                parent::processEditForm($request, $values);
        } else {
            $errors = array();
            $issues = array();
        }

        $key_name = self::PROPERTY_PHABRICATOR_NAME;
        $key_uri = self::PROPERTY_PHABRICATOR_URI;

        if (!strlen($values[$key_name])) {
            $errors[] = \Yii::t("app", 'Phabricator instance name is required.');
            $issues[$key_name] = \Yii::t("app", 'Required');
        } else if (!preg_match('/^[a-z0-9.]+\z/', $values[$key_name])) {
            $errors[] = \Yii::t("app",
                'Phabricator instance name must contain only lowercase letters, ' .
                'digits, and periods.');
            $issues[$key_name] = \Yii::t("app", 'Invalid');
        }

        if (!strlen($values[$key_uri])) {
            $errors[] = \Yii::t("app", 'Phabricator base URI is required.');
            $issues[$key_uri] = \Yii::t("app", 'Required');
        } else {
            $uri = new PhutilURI($values[$key_uri]);
            if (!$uri->getProtocol()) {
                $errors[] = \Yii::t("app",
                    'Phabricator base URI should include protocol (like "%s").',
                    'https://');
                $issues[$key_uri] = \Yii::t("app", 'Invalid');
            }
        }

        if (!$errors && $is_setup) {
            $config = $this->getProviderConfig();

            $config->setProviderDomain($values[$key_name]);
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

        $is_setup = $this->isCreate();

        $e_required = $request->isFormPost() ? null : true;

        $v_name = $values[self::PROPERTY_PHABRICATOR_NAME];
        if ($is_setup) {
            $e_name = ArrayHelper::getValue($issues, self::PROPERTY_PHABRICATOR_NAME, $e_required);
        } else {
            $e_name = null;
        }

        $v_uri = $values[self::PROPERTY_PHABRICATOR_URI];
        $e_uri = ArrayHelper::getValue($issues, self::PROPERTY_PHABRICATOR_URI, $e_required);

        if ($is_setup) {
            $form
                ->appendChild(
                    (new AphrontFormTextControl())
                        ->setLabel(\Yii::t("app", 'Phabricator Instance Name'))
                        ->setValue($v_name)
                        ->setName(self::PROPERTY_PHABRICATOR_NAME)
                        ->setError($e_name)
                        ->setCaption(\Yii::t("app",
                            'Use lowercase letters, digits, and periods. For example: %s',
                            phutil_tag(
                                'tt',
                                array(),
                                '`phabricator.oauthserver`'))));
        } else {
            $form
                ->appendChild(
                    (new AphrontFormStaticControl())
                        ->setLabel(\Yii::t("app", 'Phabricator Instance Name'))
                        ->setValue($v_name));
        }

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Phabricator Base URI'))
                    ->setValue($v_uri)
                    ->setName(self::PROPERTY_PHABRICATOR_URI)
                    ->setCaption(
                        \Yii::t("app",
                            'The URI where the OAuth server instance of Phabricator is ' .
                            'installed. For example: %s',
                            phutil_tag('tt', array(), 'https://phabricator.mycompany.com/')))
                    ->setError($e_uri));

        if (!$is_setup) {
            parent::extendEditForm($request, $form, $values, $issues);
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasSetupStep()
    {
        return true;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getPhabricatorURI()
    {
        $config = $this->getProviderConfig();
        return $config->getProperty(self::PROPERTY_PHABRICATOR_URI);
    }

}
