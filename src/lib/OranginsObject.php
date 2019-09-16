<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/11
 * Time: 1:32 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib;

use Phobject;
use ReflectionClass;
use Exception;

/**
 * Class BaseObject
 * @package orangins\lib
 * @author 陈妙威
 */
class OranginsObject extends Phobject
{
    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     * @deprecated since 2.0.14. On PHP >=5.5, use `::class` instead.
     */
    public static function className()
    {
        return get_called_class();
    }


    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name the method name
     * @return bool whether the method is defined
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    /**
     * Read the value of a class constant.
     *
     * This is the same as just typing `self::CONSTANTNAME`, but throws a more
     * useful message if the constant is not defined and allows the constant to
     * be limited to a maximum length.
     *
     * @param $key
     * @param null $byte_limit
     * @return string Value of the constant.
     * @throws Exception
     * @throws \ReflectionException
     */
    public function getPhobjectClassConstant($key, $byte_limit = null)
    {
        $class = new ReflectionClass($this);

        $const = $class->getConstant($key);
        if ($const === false) {
            throw new Exception(
                \Yii::t("app",
                    '"{0}" class "{1}" must define a "{2}" constant.',
                    [
                        __CLASS__,
                        get_class($this),
                        $key
                    ]
                )
            );
        }

        if ($byte_limit !== null) {
            if (!is_string($const) || (strlen($const) > $byte_limit)) {
                throw new Exception(
                    \Yii::t("app",
                        '"{0}" class "{1}" has an invalid "{2}" property. Field constants ' .
                        'must be strings and no more than {4} bytes in length.',
                        [
                            __CLASS__,
                            get_class($this),
                            $key,
                            $byte_limit
                        ]
                    ));
            }
        }

        return $const;
    }

    /**
     * @throws \ReflectionException
     * @return string
     * @author 陈妙威
     */
    public function getClassShortName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}