<?php

namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorContributedToObjectEdgeType
 * @package orangins\modules\transactions\edges
 * @author 陈妙威
 */
final class PhabricatorContributedToObjectEdgeType extends PhabricatorEdgeType {

    /**
     *
     */
    const EDGECONST = 34;

    /**
     * @return int|null
     * @author 陈妙威
     */public function getInverseEdgeConstant() {
    return PhabricatorObjectHasContributorEdgeType::EDGECONST;
  }

    /**
     * @return bool
     * @author 陈妙威
     */public function shouldWriteInverseTransactions() {
    return true;
  }

}
