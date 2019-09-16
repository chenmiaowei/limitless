<?php

namespace orangins\modules\metamta\query;

use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmailTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorMetaMTAApplicationEmailTransactionQuery
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorMetaMTAApplicationEmailTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorMetaMTAApplicationEmailTransaction();
    }

}
