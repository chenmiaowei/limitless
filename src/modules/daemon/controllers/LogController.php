<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/12
 * Time: 6:18 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\daemon\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\daemon\actions\PhabricatorDaemonLogListController;
use orangins\modules\daemon\actions\PhabricatorDaemonLogViewController;
use orangins\modules\daemon\actions\PhabricatorWorkerTaskDetailController;

/**
 * Class IndexController
 * @package orangins\modules\daemon\controllers
 * @author 陈妙威
 */
class LogController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
          'index' => PhabricatorDaemonLogListController::class,
          'view' => PhabricatorDaemonLogViewController::class,
        ];
    }
}