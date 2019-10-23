<?php

namespace orangins\modules\cache\purger;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorCachePurger
 * @package orangins\modules\cache\purger
 * @author 陈妙威
 */
abstract class PhabricatorCachePurger
    extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function purgeCache();

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getPurgerKey()
    {
        return $this->getPhobjectClassConstant('PURGERKEY');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllPurgers()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getPurgerKey')
            ->execute();
    }

}
