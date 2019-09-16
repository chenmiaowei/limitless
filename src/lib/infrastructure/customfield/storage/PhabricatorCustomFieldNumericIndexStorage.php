<?php
namespace orangins\lib\infrastructure\customfield\storage;

abstract class PhabricatorCustomFieldNumericIndexStorage
  extends PhabricatorCustomFieldIndexStorage {



  public function formatForInsert() {
    return qsprintf(
      $conn,
      '(%s, %s, %d)',
      $this->getObjectPHID(),
      $this->getIndexKey(),
      $this->getIndexValue());
  }

  public function getIndexValueType() {
    return 'int';
  }

}
