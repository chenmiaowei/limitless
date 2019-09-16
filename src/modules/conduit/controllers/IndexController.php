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
use orangins\modules\conduit\actions\PhabricatorConduitConsoleController;
use orangins\modules\conduit\actions\PhabricatorConduitListController;

/**
 * Class IndexController
 * @package orangins\modules\conduit\controllers
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
            'query' => PhabricatorConduitListController::class,
            'method' => PhabricatorConduitConsoleController::class,
        ];
    }
}