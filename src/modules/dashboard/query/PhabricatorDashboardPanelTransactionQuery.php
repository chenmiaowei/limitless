<?php

namespace orangins\modules\dashboard\query;

use orangins\modules\dashboard\models\PhabricatorDashboardPanelTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

final class PhabricatorDashboardPanelTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorDashboardPanelTransaction();
    }

}
