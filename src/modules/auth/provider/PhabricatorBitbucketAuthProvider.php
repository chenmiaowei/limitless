<?php
namespace orangins\modules\auth\provider;

use PhutilBitbucketAuthAdapter;

final class PhabricatorBitbucketAuthProvider
  extends PhabricatorOAuth1AuthProvider {

  public function getProviderName() {
    return \Yii::t("app",'Bitbucket');
  }

  protected function getProviderConfigurationHelp() {
    return \Yii::t("app",
      "To configure Bitbucket OAuth, log in to Bitbucket and go to ".
      "**Manage Account** > **Access Management** > **OAuth**.\n\n".
      "Click **Add Consumer** and create a new application.\n\n".
      "After completing configuration, copy the **Key** and ".
      "**Secret** to the fields above.");
  }

  protected function newOAuthAdapter() {
    return new PhutilBitbucketAuthAdapter();
  }

  protected function getLoginIcon() {
    return 'Bitbucket';
  }

}
