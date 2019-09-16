<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/17
 * Time: 1:34 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\conduitprice;

use orangins\lib\OranginsObject;
use orangins\modules\conduit\method\ConduitAPIMethod;
use PhutilClassMapQuery;

/**
 * Class UserServiceConduitPrice
 * @package orangins\modules\userservice\conduitprice
 * @author 陈妙威
 */
abstract class UserServiceConduitPrice extends OranginsObject
{
    /**
     * @return float
     * @author 陈妙威
     */
    abstract public function getPrice();

    /**
     * 每次缓存一块钱
     * @return int
     * @author 陈妙威
     */
    public static function perPrice()
    {
        return 100;
    }

    /**
     * 精度,精确到里
     * @return int
     * @author 陈妙威
     */
    public static function getPrecision()
    {
        return 1000;
    }
}