<?php
namespace orangins\modules\auth\extension;

use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;

final class PhabricatorPasswordDestructionEngineExtension
  extends PhabricatorDestructionEngineExtension {

  const EXTENSIONKEY = 'passwords';

  public function getExtensionName() {
    return \Yii::t("app",'Passwords');
  }

  public function destroyObject(
    PhabricatorDestructionEngine $engine,
    $object) {

    $viewer = $engine->getViewer();
    $object_phid = $object->getPHID();

    $passwords = PhabricatorAuthPassword::find()
      ->setViewer($viewer)
      ->withObjectPHIDs(array($object_phid))
      ->execute();

    foreach ($passwords as $password) {
      $engine->destroyObject($password);
    }
  }

}
