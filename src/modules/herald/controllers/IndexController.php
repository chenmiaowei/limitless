<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019-10-25
 * Time: 13:24
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\herald\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\herald\actions\HeraldDisableController;
use orangins\modules\herald\actions\HeraldNewController;
use orangins\modules\herald\actions\HeraldRuleAction;
use orangins\modules\herald\actions\HeraldRuleListController;
use orangins\modules\herald\actions\HeraldRuleViewController;
use orangins\modules\herald\actions\HeraldTestConsoleController;

/**
 * Class IndexController
 * @package orangins\modules\herald\controllers
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
          'query' => HeraldRuleListController::class,
          'new' => HeraldNewController::class,
          'create' => HeraldNewController::class,
          'edit' => HeraldRuleAction::class,
          'disable' => HeraldDisableController::class,
          'test' => HeraldTestConsoleController::class,
          'view' => HeraldRuleViewController::class,
        ];
    }
}