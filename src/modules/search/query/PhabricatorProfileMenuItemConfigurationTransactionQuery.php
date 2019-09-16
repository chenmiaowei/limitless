<?php

namespace orangins\modules\search\query;

use orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorProfileMenuItemConfigurationTransactionQuery
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemConfigurationTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorProfileMenuItemConfigurationTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorProfileMenuItemConfigurationTransaction();
    }

}
