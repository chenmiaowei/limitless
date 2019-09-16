<?php

namespace orangins\modules\auth\query;

use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorAuthProviderConfigTransactionQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthProviderConfigTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return PhabricatorAuthProviderConfigTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorAuthProviderConfigTransaction();
    }
}
