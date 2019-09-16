<?php
namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorSubscribedToObjectEdgeType
  extends PhabricatorEdgeType {

  const EDGECONST = 22;

  public function getInverseEdgeConstant() {
    return PhabricatorObjectHasSubscriberEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

}
