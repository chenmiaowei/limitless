<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/22
 * Time: 1:47 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\auth\actions\PhabricatorAuthOneTimeLoginAction;
use orangins\modules\auth\actions\PhabricatorAuthSetPasswordAction;
use orangins\modules\auth\actions\PhabricatorEmailLoginAction;

/**
 * Class LoginController
 * @package orangins\modules\auth\controllers
 * @author 陈妙威
 */
class LoginController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'once' => PhabricatorAuthOneTimeLoginAction::class,
            'email' => PhabricatorEmailLoginAction::class,
            'password' => PhabricatorAuthSetPasswordAction::class,
        ];
    }
}