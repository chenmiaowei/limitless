<?php

namespace orangins\modules\transactions\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\transactions\phid\TransactionPHIDType;

/**
 * Class PhabricatorDashboardApplication
 * @package orangins\modules\dashboard\application
 */
final class PhabricatorTransactionsApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'transactions';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\transactions\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/transactions/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Transactions');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Transactions Pages');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-dashboard';
    }

    public function isLaunchable() {
        return false;
    }
}
