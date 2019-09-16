<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

/**
 * Class PhabricatorWorkerArchiveTaskQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerArchiveTaskQuery
    extends PhabricatorWorkerTaskQuery
{

    /**
     * @author 陈妙威
     */
    public function execute()
    {
        $this->buildWhereClause();
        $this->buildOrderClause();
        return $this->all();
    }
}
