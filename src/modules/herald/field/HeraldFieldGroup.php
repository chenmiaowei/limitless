<?php

namespace orangins\modules\herald\field;

use orangins\modules\herald\group\HeraldGroup;
use PhutilClassMapQuery;

/**
 * Class HeraldFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
abstract class HeraldFieldGroup extends HeraldGroup
{

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getGroupKey()
    {
        return $this->getPhobjectClassConstant('FIELDGROUPKEY');
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllFieldGroups()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getGroupKey')
            ->setSortMethod('getSortKey')
            ->execute();
    }
}
