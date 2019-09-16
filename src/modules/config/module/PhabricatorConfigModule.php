<?php

namespace orangins\modules\config\module;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use PhutilClassMapQuery;

/**
 * Class PhabricatorConfigModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
abstract class PhabricatorConfigModule extends OranginsObject
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
            ->setSortMethod('getModuleName')
            ->execute();
    }

}
