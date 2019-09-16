<?php

namespace orangins\modules\oauthserver\query;

use orangins\modules\oauthserver\models\PhabricatorOAuthServerTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorOAuthServerTransactionQuery
 * @package orangins\modules\oauthserver\query
 * @author 陈妙威
 */
final class PhabricatorOAuthServerTransactionQuery extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorOAuthServerTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorOAuthServerTransaction();
    }
}
