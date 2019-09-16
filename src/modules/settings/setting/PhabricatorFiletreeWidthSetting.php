<?php
namespace orangins\modules\settings\setting;

final class PhabricatorFiletreeWidthSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'filetree.width';

  public function getSettingName() {
    return \Yii::t("app",'Filetree Width');
  }

}
