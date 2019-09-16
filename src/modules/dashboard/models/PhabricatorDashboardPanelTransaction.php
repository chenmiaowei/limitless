<?php

namespace orangins\modules\dashboard\models;

use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\dashboard\query\PhabricatorDashboardPanelTransactionQuery;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardPanelTransactionType;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class PhabricatorDashboardPanelTransaction
 * @package orangins\modules\dashboard\models
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelTransaction
    extends PhabricatorModularTransaction
{
    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "dashboard_panels_transactions";
    }

    /**
     *
     */
    const TYPE_NAME = 'dashpanel:name';
    /**
     *
     */
    const TYPE_ARCHIVE = 'dashboard:archive';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'dashboard';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorDashboardPanelPHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorDashboardPanelTransactionType::className();
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorDashboardPanelTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorDashboardPanelTransactionQuery(get_called_class());
    }
}
