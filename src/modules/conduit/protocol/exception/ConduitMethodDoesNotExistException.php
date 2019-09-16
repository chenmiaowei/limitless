<?php
namespace orangins\modules\conduit\protocol\exception;

final class ConduitMethodDoesNotExistException
  extends ConduitMethodNotFoundException {

  public function __construct($method_name) {
    parent::__construct(
      \Yii::t("app",
        'Conduit API method "{0}" does not exist.', [
              $method_name
          ]));
  }

}
