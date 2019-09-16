<?php

namespace orangins\modules\file\query;

use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorFileTransactionQuery
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorFileTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorFileTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorFileTransaction();
    }
}
