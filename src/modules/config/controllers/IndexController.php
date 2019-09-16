<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/21
 * Time: 11:31 AM
 */

namespace orangins\modules\config\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\config\actions\PhabricatorConfigAllAction;
use orangins\modules\config\actions\PhabricatorConfigApplicationAction;
use orangins\modules\config\actions\PhabricatorConfigDatabaseAction;
use orangins\modules\config\actions\PhabricatorConfigDatabaseIssueController;
use orangins\modules\config\actions\PhabricatorConfigEditAction;
use orangins\modules\config\actions\PhabricatorConfigGroupAction;
use orangins\modules\config\actions\PhabricatorConfigHistoryAction;
use orangins\modules\config\actions\PhabricatorConfigListAction;
use orangins\modules\config\actions\PhabricatorConfigModuleAction;
use orangins\modules\config\actions\PhabricatorConfigVersionAction;


/**
 * Class ConfigController
 * @package orangins\lib\controllers
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
            'index' => PhabricatorConfigListAction::class,
            'group' => PhabricatorConfigGroupAction::class,
            'application' => PhabricatorConfigApplicationAction::class,
            'all' => PhabricatorConfigAllAction::class,
            'history' => PhabricatorConfigHistoryAction::class,
            'edit' => PhabricatorConfigEditAction::class,
            'version' => PhabricatorConfigVersionAction::class,
            'database' => PhabricatorConfigDatabaseAction::class,
            'dbissue' => PhabricatorConfigDatabaseIssueController::class,
            'module' => PhabricatorConfigModuleAction::class
        ];
    }
}