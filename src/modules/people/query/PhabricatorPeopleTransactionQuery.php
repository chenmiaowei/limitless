<?php

namespace orangins\modules\people\query;

use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorPeopleTransactionQuery
 * @package orangins\modules\people\query
 * @author 陈妙威
 */
final class PhabricatorPeopleTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorUserTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorUserTransaction();
    }
}
