<?php

namespace orangins\modules\meta\models;

use orangins\modules\meta\phid\PhabricatorApplicationApplicationPHIDType;
use orangins\modules\meta\xactions\PhabricatorApplicationTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class PhabricatorApplicationApplicationTransaction
 * @package orangins\modules\meta\models
 * @author 陈妙威
 */
final class PhabricatorApplicationApplicationTransaction
    extends PhabricatorModularTransaction
{
    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "application_transaction";
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorApplicationApplicationPHIDType::TYPECONST;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorApplicationTransactionType::class;
    }

}
