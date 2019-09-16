<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorWorkerBulkJobTransactionQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorWorkerBulkJobTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorWorkerBulkJobTransaction();
    }

}
