<?php
namespace orangins\lib\export\engine;

final class PhabricatorLiskExportEngineExtension
  extends PhabricatorExportEngineExtension {

  const EXTENSIONKEY = 'lisk';

  public function supportsObject($object) {
    if (!($object instanceof LiskDAO)) {
      return false;
    }

    if (!$object->getConfigOption(LiskDAO::CONFIG_TIMESTAMPS)) {
      return false;
    }

    return true;
  }

  public function newExportFields() {
    return array(
      (new PhabricatorEpochExportField())
        ->setKey('dateCreated')
        ->setLabel(\Yii::t("app",'Created')),
      (new PhabricatorEpochExportField())
        ->setKey('dateModified')
        ->setLabel(\Yii::t("app",'Modified')),
    );
  }

  public function newExportData(array $objects) {
    $map = array();
    foreach ($objects as $object) {
      $map[] = array(
        'dateCreated' => $object->created_at,
        'dateModified' => $object->updated_at,
      );
    }
    return $map;
  }

}
