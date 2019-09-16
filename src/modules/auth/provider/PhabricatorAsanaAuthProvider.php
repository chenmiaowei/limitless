<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use PhutilAsanaAuthAdapter;

/**
 * Class PhabricatorAsanaAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorAsanaAuthProvider extends PhabricatorOAuth2AuthProvider
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Asana');
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        $app_uri = PhabricatorEnv::getProductionURI('/');
        $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

        return \Yii::t("app",
            "To configure Asana OAuth, create a new application here:" .
            "\n\n" .
            "https://app.asana.com/-/account_api" .
            "\n\n" .
            "When creating your application, use these settings:" .
            "\n\n" .
            "  - **App URL:** Set this to: `%s`\n" .
            "  - **Redirect URL:** Set this to: `%s`" .
            "\n\n" .
            "After completing configuration, copy the **Client ID** and " .
            "**Client Secret** to the fields above.",
            $app_uri,
            $login_uri);
    }

    /**
     * @return PhutilAsanaAuthAdapter
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhutilAsanaAuthAdapter();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Asana';
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function getAsanaProvider()
    {
        $providers = self::getAllEnabledProviders();

        foreach ($providers as $provider) {
            if ($provider instanceof PhabricatorAsanaAuthProvider) {
                return $provider;
            }
        }

        return null;
    }

}
