<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use PhutilAmazonAuthAdapter;
use PhutilURI;

/**
 * Class PhabricatorAmazonAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorAmazonAuthProvider
    extends PhabricatorOAuth2AuthProvider
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Amazon');
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getProviderConfigurationHelp()
    {
        $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

        $uri = new PhutilURI(PhabricatorEnv::getProductionURI('/'));
        $https_note = null;
        if ($uri->getProtocol() !== 'https') {
            $https_note = \Yii::t("app",
                'NOTE: Amazon **requires** HTTPS, but your Phabricator install does ' .
                'not use HTTPS. **You will not be able to add Amazon as an ' .
                'authentication provider until you configure HTTPS on this install**.');
        }

        return \Yii::t("app",
            "{0}\n\n" .
            "To configure Amazon OAuth, create a new 'API Project' here:" .
            "\n\n" .
            "http://login.amazon.com/manageApps" .
            "\n\n" .
            "Use these settings:" .
            "\n\n" .
            "  - **Allowed Return URLs:** Add this: `{1}`" .
            "\n\n" .
            "After completing configuration, copy the **Client ID** and " .
            "**Client Secret** to the fields above.", [
                $https_note,
                $login_uri
            ]);
    }

    /**
     * @return PhutilAmazonAuthAdapter
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhutilAmazonAuthAdapter();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Amazon';
    }

}
