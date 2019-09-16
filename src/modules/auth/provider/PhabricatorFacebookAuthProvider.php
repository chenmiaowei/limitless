<?php

namespace orangins\modules\auth\provider;

use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFacebookAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorFacebookAuthProvider
    extends PhabricatorOAuth2AuthProvider
{

    /**
     *
     */
    const KEY_REQUIRE_SECURE = 'oauth:facebook:require-secure';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Facebook');
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        $uri = PhabricatorEnv::getProductionURI($this->getLoginURI());
        return \Yii::t("app",
            'To configure Facebook OAuth, create a new Facebook Application here:' .
            "\n\n" .
            'https://developers.facebook.com/apps' .
            "\n\n" .
            'You should use these settings in your application:' .
            "\n\n" .
            "  - **Site URL**: Set this to `%s`\n" .
            "  - **Valid OAuth redirect URIs**: You should also set this to `%s`\n" .
            "  - **Client OAuth Login**: Set this to **OFF**.\n" .
            "  - **Embedded browser OAuth Login**: Set this to **OFF**.\n" .
            "\n\n" .
            "Some of these settings may be in the **Advanced** tab.\n\n" .
            "After creating your new application, copy the **App ID** and " .
            "**App Secret** to the fields above.",
            (string)$uri,
            (string)$uri);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getDefaultProviderConfig()
    {
        return parent::getDefaultProviderConfig()
            ->setProperty(self::KEY_REQUIRE_SECURE, 1);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        $require_secure = $this->getProviderConfig()->getProperty(
            self::KEY_REQUIRE_SECURE);

        return (new PhutilFacebookAuthAdapter())
            ->setRequireSecureBrowsing($require_secure);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Facebook';
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function readFormValuesFromProvider()
    {
        $require_secure = $this->getProviderConfig()->getProperty(
            self::KEY_REQUIRE_SECURE);

        return parent::readFormValuesFromProvider() + array(
                self::KEY_REQUIRE_SECURE => $require_secure,
            );
    }

    /**
     * @param AphrontRequest $request
     * @return array
     * @author 陈妙威
     */
    public function readFormValuesFromRequest(AphrontRequest $request)
    {
        return parent::readFormValuesFromRequest($request) + array(
                self::KEY_REQUIRE_SECURE => $request->getBool(self::KEY_REQUIRE_SECURE),
            );
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function extendEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues)
    {

        parent::extendEditForm($request, $form, $values, $issues);

        $key_require = self::KEY_REQUIRE_SECURE;
        $v_require = ArrayHelper::getValue($values, $key_require);

        $form
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        $key_require,
                        $v_require,
                        \Yii::t("app",
                            "%s " .
                            "Require users to enable 'secure browsing' on Facebook in order " .
                            "to use Facebook to authenticate with Phabricator. This " .
                            "improves security by preventing an attacker from capturing " .
                            "an insecure Facebook session and escalating it into a " .
                            "Phabricator session. Enabling it is recommended.",
                            phutil_tag('strong', array(), \Yii::t("app", 'Require Secure Browsing:')))));
    }

    /**
     * @param PhabricatorAuthProviderConfigTransaction $xaction
     * @return null|string

     * @throws \yii\base\Exception
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
            case self::KEY_REQUIRE_SECURE:
                if ($new) {
                    return \Yii::t("app",
                        '%s turned "Require Secure Browsing" on.',
                        $xaction->renderHandleLink($author_phid));
                } else {
                    return \Yii::t("app",
                        '%s turned "Require Secure Browsing" off.',
                        $xaction->renderHandleLink($author_phid));
                }
        }

        return parent::renderConfigPropertyTransactionTitle($xaction);
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function getFacebookApplicationID()
    {
        $providers = PhabricatorAuthProvider::getAllProviders();
        $fb_provider = ArrayHelper::getValue($providers, 'facebook:facebook.com');
        if (!$fb_provider) {
            return null;
        }

        return $fb_provider->getProviderConfig()->getProperty(
            self::PROPERTY_APP_ID);
    }

}
