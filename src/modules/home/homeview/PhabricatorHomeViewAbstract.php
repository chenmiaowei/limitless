<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/26
 * Time: 5:43 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\home\homeview;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorHomeViewAbstract
 * @package orangins\modules\home\homeview
 * @author 陈妙威
 */
abstract class PhabricatorHomeViewAbstract extends OranginsObject
{
    /**
     * @var PhabricatorUser
     */
    public  $viewer;

    /**
     * @return PhabricatorUser
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return self
     */
    public function setViewer($viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function render();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getName();

    /**
     * @return PhabricatorHomeViewAbstract[]
     * @author 陈妙威
     */
    public static function getAllTypes()
    {
        $workflows = (new PhutilClassMapQuery())
            ->setUniqueMethod("getClassShortName")
            ->setAncestorClass(__CLASS__)
            ->execute();
        return $workflows;
    }
}