<?php

namespace orangins\modules\conduit\interfaces;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorConduitSearchFieldSpecification
 * @package orangins\modules\conduit\interfaces
 * @author 陈妙威
 */
final class PhabricatorConduitSearchFieldSpecification
    extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $description;

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
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
    }

}
