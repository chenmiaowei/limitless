<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/31
 * Time: 8:19 PM
 */

namespace orangins\modules\dashboard\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\dashboard\actions\dashboard\PhabricatorDashboardAdjustController;
use orangins\modules\dashboard\actions\dashboard\PhabricatorDashboardArchiveController;
use orangins\modules\dashboard\actions\dashboard\PhabricatorDashboardEditController;
use orangins\modules\dashboard\actions\dashboard\PhabricatorDashboardInstallController;
use orangins\modules\dashboard\actions\dashboard\PhabricatorDashboardViewController;
use orangins\modules\dashboard\actions\PhabricatorDashboardConsoleController;
use orangins\modules\dashboard\actions\PhabricatorDashboardListController;

/**
 * Class IndexController
 * @package orangins\modules\dashboard\application
 * @author 陈妙威
 */
class IndexController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => PhabricatorDashboardListController::class,
            'view' => PhabricatorDashboardViewController::class,
            'archive' => PhabricatorDashboardArchiveController::class,
            'edit' => PhabricatorDashboardEditController::class,
            'install' => PhabricatorDashboardInstallController::class,
            'console' => PhabricatorDashboardConsoleController::class,
            'adjust' => PhabricatorDashboardAdjustController::class,
        ];
    }
}