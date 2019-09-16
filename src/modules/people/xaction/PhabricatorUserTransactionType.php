<?php

namespace orangins\modules\people\xaction;

use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\transactions\models\PhabricatorModularTransactionType;

/**
 * Class PhabricatorUserTransactionType
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
abstract class PhabricatorUserTransactionType extends PhabricatorModularTransactionType
{

    /**
     * @param $action
     * @return PhabricatorUserLog
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newUserLog($action)
    {
        return PhabricatorUserLog::initializeNewLog(
            $this->getActor(),
            $this->getObject()->getPHID(),
            $action);
    }
}
