<?php
namespace orangins\modules\settings\setting;

final class PhabricatorConpherenceColumnVisibleSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'conpherence-column';

  public function getSettingName() {
    return \Yii::t("app",'Conpherence Column Visible');
  }

}
