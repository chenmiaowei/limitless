<?php
namespace orangins\modules\config\check;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

final class PhabricatorInvalidConfigSetupCheck extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    $groups = PhabricatorApplicationConfigOptions::loadAll();
    foreach ($groups as $group) {
      $options = $group->getOptions();
      foreach ($options as $option) {
        try {
          $group->validateOption(
            $option,
            PhabricatorEnv::getUnrepairedEnvConfig($option->getKey()));
        } catch (PhabricatorConfigValidationException $ex) {
          $this
            ->newIssue('config.invalid.'.$option->getKey())
            ->setName(\Yii::t("app","Config '%s' Invalid", $option->getKey()))
            ->setMessage(
              \Yii::t("app",
                "Configuration option '%s' has invalid value and ".
                "was restored to the default: %s",
                $option->getKey(),
                $ex->getMessage()))
            ->addPhabricatorConfig($option->getKey());
        }
      }
    }
  }

}
