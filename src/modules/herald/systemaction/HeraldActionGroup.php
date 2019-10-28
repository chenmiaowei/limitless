<?php

namespace orangins\modules\herald\systemaction;

use orangins\modules\herald\group\HeraldGroup;
use PhutilClassMapQuery;
use PhutilInvalidStateException;

/**
 * Class HeraldActionGroup
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
abstract class HeraldActionGroup extends HeraldGroup
{

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getGroupKey()
    {
        return $this->getPhobjectClassConstant('ACTIONGROUPKEY');
    }

    /**
     * @return HeraldActionGroup[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllActionGroups()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getGroupKey')
            ->setSortMethod('getSortKey')
            ->execute();
    }
}
