<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/2
 * Time: 3:57 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\datasource;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorTagDataSourceType
 * @package orangins\modules\tag\datasource
 * @author 陈妙威
 */
abstract class PhabricatorTagDataSourceType extends OranginsObject
{
    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getAllTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getClassShortName')
            ->execute();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getName();
}