<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/21
 * Time: 5:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\transactions\datatype;

use ArrayAccess;
use ArrayIterator;
use orangins\lib\OranginsObject;
use IteratorAggregate;
use Traversable;
use yii\helpers\Json;

/**
 * Class ArrayDataType
 * @package orangins\modules\transactions\datatype
 * @author 陈妙威
 */
class ArrayDataType extends OranginsObject implements ArrayAccess, IteratorAggregate
{
    /**
     * @var
     */
    public $array;

    /**
     * ArrayDataType constructor.
     * @param $array
     */
    public function __construct($array)
    {
        $this->array = $array;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function __toString()
    {
        return Json::encode($this->array);
    }

    /**
     * @param mixed $offset
     * @return bool
     * @author 陈妙威
     */
    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->array);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @author 陈妙威
     */
    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @author 陈妙威
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @author 陈妙威
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    /**
     * @return ArrayIterator|Traversable
     * @author 陈妙威
     */
    public function getIterator()
    {
        return new ArrayIterator($this->array);
    }
}