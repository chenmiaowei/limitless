<?php

namespace orangins\lib\db;

use Exception;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\view\control\AphrontCursorPagerView;
use PhutilBufferedIterator;

/**
 * Class PhabricatorQueryIterator
 * @package orangins\lib\db
 * @author 陈妙威
 */
final class PhabricatorQueryIterator extends PhutilBufferedIterator
{

    /**
     * @var PhabricatorCursorPagedPolicyAwareQuery
     */
    private $query;
    /**
     * @var AphrontCursorPagerView
     */
    private $pager;

    /**
     * PhabricatorQueryIterator constructor.
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     */
    public function __construct(PhabricatorCursorPagedPolicyAwareQuery $query)
    {
        $this->query = $query;
    }

    /**
     * @author 陈妙威
     */
    protected function didRewind()
    {
        $this->pager = new AphrontCursorPagerView();
    }

    /**
     * @return scalar
     * @author 陈妙威
     */
    public function key()
    {
        return $this->current()->getID();
    }

    /**
     * @return array|mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        if (!$this->pager) {
            return array();
        }

        $pager = clone $this->pager;
        $query = clone $this->query;

        $query->setDisableOverheating(true);

        $results = $query->executeWithCursorPager($pager);

        // If we got less than a full page of results, this was the last set of
        // results. Throw away the pager so we end iteration.
        if (!$pager->getHasMoreResults()) {
            $this->pager = null;
        } else {
            $this->pager->setAfterID($pager->getNextPageID());
        }

        return $results;
    }

}
