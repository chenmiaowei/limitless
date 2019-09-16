<?php

namespace orangins\modules\guides\module;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;

/**
 * Class PhabricatorGuideModule
 * @package orangins\modules\guides\module
 * @author 陈妙威
 */
abstract class PhabricatorGuideModule extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getModuleKey();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getModuleName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getModulePosition();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getIsModuleEnabled();

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderModuleStatus(AphrontRequest $request);

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllModules()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getModuleKey')
            ->setSortMethod('getModulePosition')
            ->execute();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getEnabledModules()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getModuleKey')
            ->setSortMethod('getModulePosition')
            ->setFilterMethod('getIsModuleEnabled')
            ->execute();
    }

}
