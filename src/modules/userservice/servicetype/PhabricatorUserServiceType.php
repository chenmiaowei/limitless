<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/16
 * Time: 2:55 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\userservice\servicetype;

use orangins\lib\OranginsObject;
use orangins\lib\view\form\AphrontFormView;
use PhutilClassMapQuery;

/**
 * Class PhabricatorUserServiceType
 * @package orangins\modules\userservice\servicetype
 * @author 陈妙威
 */
abstract class PhabricatorUserServiceType extends OranginsObject
{
    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getName();

     /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getIcon();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getKey();


    /**
     * @return PhabricatorUserServiceType[]
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