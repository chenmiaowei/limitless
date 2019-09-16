<?php

namespace orangins\modules\config\query;

use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorConfigTransactionQuery
 * @package orangins\modules\config\query
 * @author 陈妙威
 */
final class PhabricatorConfigTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorConfigTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorConfigTransaction();
    }


}
