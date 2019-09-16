<?php
namespace orangins\modules\settings\setting;

final class PhabricatorDiffusionBlameSetting
  extends PhabricatorInternalSetting {

  const SETTINGKEY = 'diffusion-blame';

  public function getSettingName() {
    return \Yii::t("app",'Diffusion Blame');
  }

  public function getSettingDefaultValue() {
    return false;
  }

}
