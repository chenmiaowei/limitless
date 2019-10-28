<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\field\HeraldField;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;

/**
 * Class PhabricatorMailEmailHeraldField
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
abstract class PhabricatorMailEmailHeraldField
    extends HeraldField
{

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorMetaMTAMail);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return PhabricatorMailEmailHeraldFieldGroup::FIELDGROUPKEY;
    }
}
