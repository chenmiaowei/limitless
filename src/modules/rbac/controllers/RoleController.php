<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/29
 * Time: 9:58 AM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\rbac\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\rbac\actions\PhabricatorRBACRoleAddCapabilityAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleAddUserAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleEditAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleListAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleRemoveCapabilityAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleRemoveUserAction;
use orangins\modules\rbac\actions\PhabricatorRBACRoleViewAction;

/**
 * Class IndexController
 * @package orangins\modules\rbac\controllers
 * @author 陈妙威
 */
class RoleController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => PhabricatorRBACRoleListAction::className(),
            'create' => PhabricatorRBACRoleEditAction::className(),
            'edit' => PhabricatorRBACRoleEditAction::className(),
            'view' => PhabricatorRBACRoleViewAction::className(),
            'add-capability' => PhabricatorRBACRoleAddCapabilityAction::className(),
            'remove-capability' => PhabricatorRBACRoleRemoveCapabilityAction::className(),
            'add-user' => PhabricatorRBACRoleAddUserAction::className(),
            'remove-user' => PhabricatorRBACRoleRemoveUserAction::className(),
        ];
    }
}