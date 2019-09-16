<?php
namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use PhutilSlackAuthAdapter;

/**
 * Class PhabricatorSlackAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorSlackAuthProvider
  extends PhabricatorOAuth2AuthProvider {

    /**
     * @return string
     * @author 陈妙威
     */public function getProviderName() {
    return \Yii::t("app",'Slack');
  }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */protected function getProviderConfigurationHelp() {
    $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

    return \Yii::t("app",
      "To configure Slack OAuth, create a new application here:".
      "\n\n".
      "https://api.slack.com/docs/sign-in-with-slack#create_slack_app".
      "\n\n".
      "When creating your application, use these settings:".
      "\n\n".
      "  - **Redirect URI:** Set this to: `%s`".
      "\n\n".
      "After completing configuration, copy the **Client ID** and ".
      "**Client Secret** to the fields above. (You may need to generate the ".
      "client secret by clicking 'New Secret' first.)",
      $login_uri);
  }

    /**
     * @return PhutilSlackAuthAdapter
     * @author 陈妙威
     */protected function newOAuthAdapter() {
    return new PhutilSlackAuthAdapter();
  }

    /**
     * @return string
     * @author 陈妙威
     */protected function getLoginIcon() {
    return 'Slack';
  }

}
