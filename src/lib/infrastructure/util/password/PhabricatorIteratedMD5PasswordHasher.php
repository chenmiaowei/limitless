<?php
namespace orangins\lib\infrastructure\util\password;

use PhutilOpaqueEnvelope;

final class PhabricatorIteratedMD5PasswordHasher
  extends PhabricatorPasswordHasher {

  public function getHumanReadableName() {
    return \Yii::t("app",'Iterated MD5');
  }

  public function getHashName() {
    return 'md5';
  }

  public function getHashLength() {
    return 32;
  }

  public function canHashPasswords() {
    return function_exists('md5');
  }

  public function getInstallInstructions() {
    // This should always be available, but do something useful anyway.
    return \Yii::t("app",'To use iterated MD5, make the md5() function available.');
  }

  public function getStrength() {
    return 1.0;
  }

  public function getHumanReadableStrength() {
    return \Yii::t("app",'Okay');
  }

  protected function getPasswordHash(PhutilOpaqueEnvelope $envelope) {
    $raw_input = $envelope->openEnvelope();

    $hash = $raw_input;
    for ($ii = 0; $ii < 1000; $ii++) {
      $hash = md5($hash);
    }

    return new PhutilOpaqueEnvelope($hash);
  }

}
