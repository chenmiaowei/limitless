<?php
namespace orangins\modules\settings\setting;

final class PhabricatorConpherenceColumnMinimizeSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'conpherence-minimize-column';

  public function getSettingName() {
    return \Yii::t("app",'Conpherence Column Minimize');
  }

}
