<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

/**
 * Class PhabricatorWorkerActiveTaskQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerActiveTaskQuery
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
