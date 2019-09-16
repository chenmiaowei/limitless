<?php
namespace orangins\lib\export\engine;

final class PhabricatorExportFormatSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'export.format';

  public function getSettingName() {
    return \Yii::t("app",'Export Format');
  }

  public function getSettingDefaultValue() {
    return null;
  }

}
