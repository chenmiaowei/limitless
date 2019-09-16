<?php
namespace orangins\modules\settings\setting;

final class PhabricatorConpherenceWidgetVisibleSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'conpherence-widget';

  public function getSettingName() {
    return \Yii::t("app",'Conpherence Widget Pane Visible');
  }

}
