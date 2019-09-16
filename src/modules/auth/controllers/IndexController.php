<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/5
 * Time: 1:26 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\controllers;

use orangins\modules\auth\actions\config\PhabricatorAuthListAction;
use orangins\modules\auth\actions\PhabricatorAuthFinishAction;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\actions\PhabricatorAuthRegisterAction;
use orangins\modules\auth\actions\PhabricatorAuthStartAction;
use orangins\modules\auth\actions\PhabricatorAuthValidateAction;
use orangins\modules\auth\actions\PhabricatorLogoutAction;
use orangins\lib\controllers\PhabricatorController;
use yii\filters\AccessControl;

/**
 * Class IndexController
 * @package orangins\modules\auth\controllers
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
            'start' => PhabricatorAuthListAction::class,
            'logout' => PhabricatorLogoutAction::class,
            'login' => PhabricatorAuthLoginAction::class,
            'register' => PhabricatorAuthRegisterAction::class,
            'validate' => PhabricatorAuthValidateAction::class,
            'finish' => PhabricatorAuthFinishAction::class,
            'index' => PhabricatorAuthListAction::class,
            'loggedout' => PhabricatorAuthStartAction::class,
        ];
    }
}