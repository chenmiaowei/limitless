<?php

namespace orangins\modules\file\iconset;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorIconSetIcon
 * @package orangins\modules\file\iconset
 * @author 陈妙威
 */
final class PhabricatorIconSetIcon extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $label;
    /**
     * @var
     */
    private $isDisabled;

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
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIcon()
    {
        if ($this->icon === null) {
            return $this->getKey();
        }
        return $this->icon;
    }

    /**
     * @param $is_disabled
     * @return $this
     * @author 陈妙威
     */
    public function setIsDisabled($is_disabled)
    {
        $this->isDisabled = $is_disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLabel()
    {
        return $this->label;
    }

}
