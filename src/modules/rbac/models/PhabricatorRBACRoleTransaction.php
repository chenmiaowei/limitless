<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 5:47 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\models;


use orangins\modules\rbac\phid\PhabricatorRBACRolePHIDType;
use orangins\modules\rbac\query\PhabricatorRBACRoleTransactionQuery;
use orangins\modules\rbac\xaction\PhabricatorRBACRoleTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class PhabricatorRBACRoleTransaction
 * @package orangins\modules\rbac\models
 * @author 陈妙威
 */
class PhabricatorRBACRoleTransaction extends PhabricatorModularTransaction
{

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "rbac_role_transactions";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'rbac';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorRBACRolePHIDType::TYPECONST;
    }

    /**
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorRBACRoleTransactionType::class;
    }

    /**
     * @return PhabricatorRBACRoleTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorRBACRoleTransactionQuery(get_called_class());
    }
}