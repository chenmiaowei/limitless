<?php
namespace orangins\modules\settings\setting;

final class PhabricatorDarkConsoleVisibleSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'darkconsole.visible';

  public function getSettingName() {
    return \Yii::t("app",'DarkConsole Visible');
  }

}
