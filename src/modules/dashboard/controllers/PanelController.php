<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/1
 * Time: 11:09 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\dashboard\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelArchiveController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelEditController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelListController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelRenderController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelTabsController;
use orangins\modules\dashboard\actions\panel\PhabricatorDashboardPanelViewController;
use orangins\modules\dashboard\actions\PhabricatorDashboardQueryPanelInstallController;

/**
 * Class PanelController
 * @package orangins\modules\dashboard\controllers
 * @author 陈妙威
 */
class PanelController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'install' => PhabricatorDashboardQueryPanelInstallController::class,
            'query' => PhabricatorDashboardPanelListController::class,
            'edit' => PhabricatorDashboardPanelEditController::class,
            'render' => PhabricatorDashboardPanelRenderController::class,
            'archive' => PhabricatorDashboardPanelArchiveController::class,
            'tabs' => PhabricatorDashboardPanelTabsController::class,
            'view' => PhabricatorDashboardPanelViewController::className(),
        ];
    }
}