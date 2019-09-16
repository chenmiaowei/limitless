<?php

namespace orangins\modules\auth\xaction;

use orangins\modules\transactions\models\PhabricatorModularTransactionType;

/**
 * Class PhabricatorAuthFactorProviderTransactionType
 * @package orangins\modules\auth\xaction
 * @author 陈妙威
 */
abstract class PhabricatorAuthFactorProviderTransactionType
    extends PhabricatorModularTransactionType
{

    /**
     * @param PhabricatorAuthFactorProvider $provider
     * @return bool
     * @author 陈妙威
     */
    final protected function isDuoProvider(
        PhabricatorAuthFactorProvider $provider)
    {
        $duo_key = id(new PhabricatorDuoAuthFactor())->getFactorKey();
        return ($provider->getProviderFactorKey() === $duo_key);
    }

}
