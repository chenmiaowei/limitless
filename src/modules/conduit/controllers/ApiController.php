<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/8
 * Time: 11:15 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\conduit\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\conduit\actions\PhabricatorConduitAPIController;
use orangins\modules\conduit\actions\PhabricatorConduitConsoleController;
use orangins\modules\conduit\actions\PhabricatorConduitHuaweiNotifyController;
use orangins\modules\conduit\actions\PhabricatorConduitListController;
use orangins\modules\conduit\actions\PhabricatorConduitTokenEditController;
use orangins\modules\conduit\actions\PhabricatorConduitTokenTerminateController;

/**
 * Class IndexController
 * @package orangins\modules\conduit\controllers
 * @author 陈妙威
 */
class ApiController extends PhabricatorController
{
    /**
     * @var bool
     */
    public $enableCsrfValidation = false;
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'index' => PhabricatorConduitAPIController::class,
            'huawei' => PhabricatorConduitHuaweiNotifyController::class,
        ];
    }
}