<?php
namespace orangins\modules\search\interfaces;

interface PhabricatorNgramsInterface
  extends PhabricatorIndexableInterface {

  public function newNgrams();

}
