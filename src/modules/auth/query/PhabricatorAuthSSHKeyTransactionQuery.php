<?php

namespace orangins\modules\auth\query;

use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorAuthSSHKeyTransactionQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorAuthSSHKeyTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorAuthSSHKeyTransaction();
    }

}
