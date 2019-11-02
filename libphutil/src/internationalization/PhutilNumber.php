<?php

/**
 * Class PhutilNumber
 * @author 陈妙威
 */
final class PhutilNumber extends Phobject
{

    /**
     * @var
     */
    private $value;
    /**
     * @var int
     */
    private $decimals = 0;

    /**
     * PhutilNumber constructor.
     * @param $value
     * @param int $decimals
     */
    public function __construct($value, $decimals = 0)
    {
        $this->value = $value;
        $this->decimals = $decimals;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNumber()
    {
        return $this->value;
    }

    /**
     * @param $decimals
     * @return $this
     * @author 陈妙威
     */
    public function setDecimals($decimals)
    {
        $this->decimals = $decimals;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getDecimals()
    {
        return $this->decimals;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function __toString()
    {
        return bcadd($this->value, 0, $this->decimals);
    }
}
