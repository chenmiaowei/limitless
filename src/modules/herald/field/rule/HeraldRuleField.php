<?php

namespace orangins\modules\herald\field\rule;

use orangins\modules\herald\field\HeraldField;

/**
 * Class HeraldRuleField
 * @package orangins\modules\herald\field\rule
 * @author 陈妙威
 */
abstract class HeraldRuleField
    extends HeraldField
{

    /**
     * @return |null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return ManiphestTaskHeraldFieldGroup::FIELDGROUPKEY;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof HeraldRule);
    }

}
