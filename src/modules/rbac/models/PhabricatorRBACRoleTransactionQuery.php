<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 5:50 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\query;


use orangins\modules\rbac\models\PhabricatorRBACRoleTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorRBACRoleTransactionQuery
 * @package orangins\modules\rbac\query
 * @author 陈妙威
 */
class PhabricatorRBACRoleTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorRBACRoleTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorRBACRoleTransaction();
    }
}
