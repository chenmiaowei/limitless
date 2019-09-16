<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/11
 * Time: 3:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\controllers;


use orangins\modules\auth\actions\config\PhabricatorAuthEditAction;
use orangins\modules\auth\actions\config\PhabricatorAuthNewAction;
use orangins\lib\controllers\PhabricatorController;

/**
 * Class ConfigController
 * @package orangins\modules\auth\controllers
 * @author 陈妙威
 */
class ConfigController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'new' => PhabricatorAuthNewAction::class,
            'edit' => PhabricatorAuthEditAction::class,
        ];
    }
}