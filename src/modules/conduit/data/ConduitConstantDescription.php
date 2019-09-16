<?php

namespace orangins\modules\conduit\data;

use orangins\lib\OranginsObject;

/**
 * Class ConduitConstantDescription
 * @package orangins\modules\conduit\data
 * @author 陈妙威
 */
final class ConduitConstantDescription extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $isDeprecated;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $is_deprecated
     * @return $this
     * @author 陈妙威
     */
    public function setIsDeprecated($is_deprecated)
    {
        $this->isDeprecated = $is_deprecated;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsDeprecated()
    {
        return $this->isDeprecated;
    }

}
