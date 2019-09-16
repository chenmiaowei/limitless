<?php

namespace orangins\modules\auth\query;

use orangins\modules\auth\models\PhabricatorAuthPasswordTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorAuthPasswordTransactionQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorAuthPasswordTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorAuthPasswordTransaction();
    }
}
