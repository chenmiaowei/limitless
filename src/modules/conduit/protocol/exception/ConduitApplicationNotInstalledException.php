<?php
namespace orangins\modules\conduit\protocol\exception;

use orangins\modules\conduit\method\ConduitAPIMethod;

final class ConduitApplicationNotInstalledException
  extends ConduitMethodNotFoundException {

  public function __construct(ConduitAPIMethod $method, $application) {
    parent::__construct(
      \Yii::t("app",
        "Method '{0}' belongs to application '{1}', which is not installed.", [
              $method->getAPIMethodName(),
              $application
          ]));
  }

}
