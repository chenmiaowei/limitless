<?php

namespace orangins\modules\file\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use orangins\modules\transactions\edges\PhabricatorObjectHasFileEdgeType;

/**
 * Class PhabricatorFileHasObjectEdgeType
 * @package orangins\modules\file\edge
 * @author 陈妙威
 */
final class PhabricatorFileHasObjectEdgeType extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 26;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorObjectHasFileEdgeType::EDGECONST;
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
