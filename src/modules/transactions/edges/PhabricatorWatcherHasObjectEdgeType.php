<?php
namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorWatcherHasObjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 48;

  public function getInverseEdgeConstant() {
    return PhabricatorObjectHasWatcherEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

}
