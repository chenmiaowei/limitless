<?php

namespace orangins\modules\config\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\config\application\PhabricatorConfigApplication;
use orangins\modules\config\models\PhabricatorConfigEntry;

/**
 * Class PhabricatorConfigEntryQuery
 * @package orangins\modules\config\query
 * @author 陈妙威
 */
final class PhabricatorConfigEntryQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $ids;

    /**
     * @param $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs($ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs($phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws \Exception
     */
    protected function loadPage()
    {
        $this->buildWhereClause();
        $this->buildOrderClause();
        $this->buildLimitClause();
        return $this->all();
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorConfigApplication::class;
    }

}
