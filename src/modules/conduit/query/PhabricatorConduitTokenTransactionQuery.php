<?php

namespace orangins\modules\conduit\query;

use orangins\modules\conduit\models\PhabricatorConduitTokenTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorFileTransactionQuery
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorConduitTokenTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorConduitTokenTransaction();
    }
}
