<?php

namespace orangins\modules\userservice\models;

use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * This is the ActiveQuery class for [[UserserviceTransactions]].
 *
 * @see PhabricatorXgbzxrStatusTransaction
 */
class UserserviceTransactionsQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorUserServiceTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorUserServiceTransaction();
    }
}