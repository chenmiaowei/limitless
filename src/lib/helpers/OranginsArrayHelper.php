<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/21
 * Time: 8:45 PM
 */

namespace orangins\lib\helpers;


use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class OranginsArrayHelper
 * @package orangins\lib\helpers
 */
class OranginsArrayHelper extends OranginsObject
{
    /**
     * @param $array
     * @param \Closure $closure
     * @return array
     */
    public static function mulmap($array, \Closure $closure)
    {
        $result = [];
        foreach ($array as $item) {
            $call_user_func = call_user_func($closure, $item);
            if($call_user_func !== null) {
                $result[] = $call_user_func;
            }
        }
        return $result;
    }

    /**
     * @param $needle
     * @param $array
     * @param $column
     * @return int|null|string
     */
    public static function search($needle, $array, $column)
    {
        $columns = ArrayHelper::getColumn($array, $column);
        foreach ($columns as $key => $val) {
            if ($val === $needle) {
                return $key;
            }
        }
        return null;
    }
}