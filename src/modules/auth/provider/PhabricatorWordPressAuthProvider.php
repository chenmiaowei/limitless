<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use PhutilWordPressAuthAdapter;

/**
 * Class PhabricatorWordPressAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorWordPressAuthProvider
    extends PhabricatorOAuth2AuthProvider
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'WordPress.com');
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        $uri = PhabricatorEnv::getProductionURI('/');
        $callback_uri = PhabricatorEnv::getURI($this->getLoginURI());

        return \Yii::t("app",
            "To configure WordPress.com OAuth, create a new WordPress.com " .
            "Application here:\n\n" .
            "https://developer.wordpress.com/apps/new/." .
            "\n\n" .
            "You should use these settings in your application:" .
            "\n\n" .
            "  - **URL:** Set this to your full domain with protocol. For this " .
            "    Phabricator install, the correct value is: `%s`\n" .
            "  - **Redirect URL**: Set this to: `%s`\n" .
            "\n\n" .
            "Once you've created an application, copy the **Client ID** and " .
            "**Client Secret** into the fields above.",
            $uri,
            $callback_uri);
    }

    /**
     * @return PhutilWordPressAuthAdapter
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhutilWordPressAuthAdapter();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'WordPressCOM';
    }
}
