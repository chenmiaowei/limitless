<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 11:34 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\userservice\actions\ManiphestBulkEditController;
use orangins\modules\userservice\actions\PhabricatorUserServiceCreateAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceDepositAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceDisableAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceEditAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceListAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceRenewAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceStartAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceStopAction;
use orangins\modules\userservice\actions\PhabricatorUserServiceViewAction;

/**
 * Class IndexController
 * @package orangins\modules\userservice\controllers
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
            'edit' => PhabricatorUserServiceEditAction::class,
            'view' => PhabricatorUserServiceViewAction::class,
            'query' => PhabricatorUserServiceListAction::class,
            'create' => PhabricatorUserServiceCreateAction::class,
            'deposit' => PhabricatorUserServiceDepositAction::class,
            'renew' => PhabricatorUserServiceRenewAction::class,
            'disable' => PhabricatorUserServiceDisableAction::class,
            'bulk' => ManiphestBulkEditController::class,
            'stop' => PhabricatorUserServiceStopAction::class,
            'start' => PhabricatorUserServiceStartAction::class,
        ];
    }
}