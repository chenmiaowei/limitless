<?php
namespace orangins\modules\transactions\xaction;

use orangins\modules\transactions\models\PhabricatorModularTransactionType;

/**
 * Class PhabricatorCoreVoidTransaction
 * @package orangins\modules\transactions\xaction
 * @author 陈妙威
 */
final class PhabricatorCoreVoidTransaction
  extends PhabricatorModularTransactionType {

    /**
     *
     */
    const TRANSACTIONTYPE = 'core.void';

}
