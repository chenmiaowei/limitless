<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 5:50 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\query;


use orangins\modules\tag\models\PhabricatorTagTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorTagTransactionQuery
 * @package orangins\modules\tag\query
 * @author 陈妙威
 */
class PhabricatorTagTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorTagTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorTagTransaction();
    }
}
