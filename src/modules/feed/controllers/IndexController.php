<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/27
 * Time: 10:42 PM
 */

namespace orangins\modules\feed\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\feed\actions\PhabricatorFeedDetailController;
use orangins\modules\feed\actions\PhabricatorFeedListController;

/**
 * Class IndexController
 * @package orangins\modules\feed\controllers
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
            'query' => PhabricatorFeedListController::className(),
            'view' => PhabricatorFeedDetailController::className()
        ];
    }
}