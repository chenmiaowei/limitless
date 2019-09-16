<?php

namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorMutedByEdgeType
 * @package orangins\modules\transactions\edges
 * @author 陈妙威
 */
final class PhabricatorMutedByEdgeType
    extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 68;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorMutedEdgeType::EDGECONST;
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
