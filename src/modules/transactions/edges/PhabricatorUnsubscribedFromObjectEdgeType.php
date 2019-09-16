<?php
namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorUnsubscribedFromObjectEdgeType
  extends PhabricatorEdgeType {

  const EDGECONST = 24;

  public function getInverseEdgeConstant() {
    return PhabricatorObjectHasUnsubscriberEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

}
