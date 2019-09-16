<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/3
 * Time: 6:53 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\edge;


use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorTagToObjectEdgeType
 * @package orangins\modules\tag\edge
 * @author 陈妙威
 */
class PhabricatorTagToObjectEdgeType
    extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 102;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorObjectHasTagEdgeType::EDGECONST;
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
