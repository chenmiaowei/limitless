<?php

namespace orangins\modules\settings\query;

use orangins\modules\settings\models\PhabricatorUserPreferencesTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorUserPreferencesTransactionQuery
 * @package orangins\modules\settings\query
 * @author 陈妙威
 */
final class PhabricatorUserPreferencesTransactionQuery extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorUserPreferencesTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorUserPreferencesTransaction();
    }
}
