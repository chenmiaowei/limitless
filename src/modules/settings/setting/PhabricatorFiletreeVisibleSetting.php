<?php
namespace orangins\modules\settings\setting;

final class PhabricatorFiletreeVisibleSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'nav-collapsed';

  public function getSettingName() {
    return \Yii::t("app",'Filetree Visible');
  }

}
