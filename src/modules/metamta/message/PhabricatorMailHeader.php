<?php

namespace orangins\modules\metamta\message;

use Phobject;

/**
 * Class PhabricatorMailHeader
 * @package orangins\modules\metamta\message
 * @author 陈妙威
 */
final class PhabricatorMailHeader
    extends Phobject
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $value;

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
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

}
