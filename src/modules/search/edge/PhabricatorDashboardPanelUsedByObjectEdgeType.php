<?php

namespace orangins\modules\search\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorDashboardPanelUsedByObjectEdgeType
 * @package orangins\modules\search\edge
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelUsedByObjectEdgeType
  extends PhabricatorEdgeType {

    /**
     *
     */
    const EDGECONST = 72;

    /**
     * @return int|null
     * @author 陈妙威
     */public function getInverseEdgeConstant() {
    return PhabricatorObjectUsesDashboardPanelEdgeType::EDGECONST;
  }

    /**
     * @return bool
     * @author 陈妙威
     */public function shouldWriteInverseTransactions() {
    return true;
  }

}
