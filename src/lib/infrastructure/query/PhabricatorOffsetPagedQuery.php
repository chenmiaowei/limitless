<?php

namespace orangins\lib\infrastructure\query;

use orangins\lib\infrastructure\query\PhabricatorQuery;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\lib\view\phui\PHUIPagerView;

/**
 * A query class which uses offset/limit paging. Provides logic and accessors
 * for offsets and limits.
 */
abstract class PhabricatorOffsetPagedQuery extends PhabricatorQuery
{
    /**
     * @param $offset
     * @return $this
     * @author 陈妙威
     */
    final public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    final public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param PHUIPagerView|AphrontCursorPagerView $pager
     * @return array
     * @author 陈妙威
     */
    final public function executeWithOffsetPager(PHUIPagerView $pager)
    {
        $this->setLimit($pager->getPageSize() + 1);
        $this->setOffset($pager->getOffset());

        $results = $this->execute();

        return $pager->sliceResults($results);
    }
}
