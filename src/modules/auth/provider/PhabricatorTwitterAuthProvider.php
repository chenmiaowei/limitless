<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use PhutilTwitterAuthAdapter;

/**
 * Class PhabricatorTwitterAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorTwitterAuthProvider
    extends PhabricatorOAuth1AuthProvider
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Twitter');
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

        return \Yii::t("app",
            "To configure Twitter OAuth, create a new application here:" .
            "\n\n" .
            "https://dev.twitter.com/apps" .
            "\n\n" .
            "When creating your application, use these settings:" .
            "\n\n" .
            "  - **Callback URL:** Set this to: `%s`" .
            "\n\n" .
            "After completing configuration, copy the **Consumer Key** and " .
            "**Consumer Secret** to the fields above.",
            $login_uri);
    }

    /**
     * @return PhutilTwitterAuthAdapter
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhutilTwitterAuthAdapter();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'Twitter';
    }
}
