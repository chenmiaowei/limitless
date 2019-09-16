<?php

namespace orangins\modules\dashboard\query;

use orangins\modules\dashboard\models\PhabricatorDashboardTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorDashboardTransactionQuery
 * @package orangins\modules\dashboard\query
 * @author 陈妙威
 */
final class PhabricatorDashboardTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{
    /**
     * @return mixed|PhabricatorDashboardTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorDashboardTransaction();
    }
}
