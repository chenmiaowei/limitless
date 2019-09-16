<?php

namespace orangins\modules\meta\query;

use orangins\modules\meta\models\PhabricatorApplicationApplicationTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorApplicationApplicationTransactionQuery
 * @package orangins\modules\meta\query
 * @author 陈妙威
 */
final class PhabricatorApplicationApplicationTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorApplicationApplicationTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorApplicationApplicationTransaction();
    }
}
