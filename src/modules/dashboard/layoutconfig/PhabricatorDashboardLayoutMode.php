<?php

namespace orangins\modules\dashboard\layoutconfig;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorDashboardLayoutMode
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
abstract class PhabricatorDashboardLayoutMode
    extends OranginsObject
{

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getLayoutModeKey()
    {
        return $this->getPhobjectClassConstant('LAYOUTMODE', 32);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getLayoutModeOrder()
    {
        return 1000;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getLayoutModeName();

    /**
     * @return PhabricatorDashboardColumn[]
     * @author 陈妙威
     */
    abstract public function getLayoutModeColumns();

    /**
     * @return PhabricatorDashboardColumn
     * @author 陈妙威
     */
    final protected function newColumn()
    {
        return new PhabricatorDashboardColumn();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllLayoutModes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getLayoutModeKey')
            ->setSortMethod('getLayoutModeOrder')
            ->execute();
    }

    /**
     * @return \dict
     * @author 陈妙威
     */
    final public static function getLayoutModeMap()
    {
        $modes = self::getAllLayoutModes();
        return mpull($modes, 'getLayoutModeName', 'getLayoutModeKey');
    }

}
