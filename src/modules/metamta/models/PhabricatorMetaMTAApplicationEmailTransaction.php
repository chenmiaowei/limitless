<?php

namespace orangins\modules\metamta\models;

use orangins\modules\phid\PhabricatorMetaMTAApplicationEmailPHIDType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorMetaMTAApplicationEmailTransaction
 * @package orangins\modules\metamta\models
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailTransaction
    extends PhabricatorApplicationTransaction
{

    /**
     *
     */
    const KEY_CONFIG = 'appemail.config.key';

    /**
     *
     */
    const TYPE_ADDRESS = 'appemail.address';
    /**
     *
     */
    const TYPE_CONFIG = 'appemail.config';

    public static function tableName()
    {
        return "metamta_applicationemailtransaction";
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'metamta';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorMetaMTAApplicationEmailPHIDType::TYPECONST;
    }

}
