<?php
namespace orangins\modules\settings\setting;

final class PhabricatorDarkConsoleTabSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'darkconsole.tab';

  public function getSettingName() {
    return \Yii::t("app",'DarkConsole Tab');
  }

}
