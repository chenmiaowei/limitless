<?php

namespace orangins\modules\search\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorObjectUsesDashboardPanelEdgeType
 * @package orangins\modules\search\edge
 * @author 陈妙威
 */
final class PhabricatorObjectUsesDashboardPanelEdgeType
    extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 71;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorDashboardPanelUsedByObjectEdgeType::EDGECONST;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldWriteInverseTransactions()
    {
        return true;
    }

}
