<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 11:34 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\controllers;

use orangins\lib\controllers\PhabricatorController;
use app\task\actions\PhabricatorTaskEditAction;
use app\task\actions\PhabricatorTaskPublishTypeAction;
use app\task\actions\PhabricatorTaskSettleAction;
use orangins\modules\widgets\actions\PhabricatorWidgetsUEditorAction;

/**
 * Class IndexController
 * @package app\task\controllers
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
            'ueditor' => PhabricatorWidgetsUEditorAction::class,
        ];
    }
}