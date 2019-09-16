<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/14
 * Time: 11:10 AM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\finance\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\finance\actions\FinanceDashboardAction;
use orangins\modules\finance\actions\FinanceDepositAction;

/**
 * Class IndexController
 * @package orangins\modules\finance\controllers
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
            'dashboard' => FinanceDashboardAction::class,
            'deposit' => FinanceDepositAction::class,
        ];
    }
}