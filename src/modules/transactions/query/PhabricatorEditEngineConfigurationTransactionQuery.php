<?php

namespace orangins\modules\transactions\query;

use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;

/**
 * Class PhabricatorEditEngineConfigurationTransactionQuery
 * @package orangins\modules\transactions\query
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationTransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorEditEngineConfigurationTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new PhabricatorEditEngineConfigurationTransaction();
    }
}
