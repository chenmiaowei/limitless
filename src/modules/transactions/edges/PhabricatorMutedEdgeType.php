<?php

namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorMutedEdgeType
 * @package orangins\modules\transactions\edges
 * @author 陈妙威
 */
final class PhabricatorMutedEdgeType
    extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 67;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorMutedByEdgeType::EDGECONST;
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
