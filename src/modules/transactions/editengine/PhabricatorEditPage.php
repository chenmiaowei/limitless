<?php

namespace orangins\modules\transactions\editengine;


use orangins\lib\OranginsObject;

/**
 * Class PhabricatorEditPage
 * @package orangins\modules\transactions\editengine
 * @author 陈妙威
 */
final class PhabricatorEditPage
    extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $label;
    /**
     * @var array
     */
    private $fieldKeys = array();
    /**
     * @var
     */
    private $viewURI;
    /**
     * @var
     */
    private $isDefault;

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

    /**
     * @param array $field_keys
     * @return $this
     * @author 陈妙威
     */
    public function setFieldKeys(array $field_keys)
    {
        $this->fieldKeys = $field_keys;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldKeys()
    {
        return $this->fieldKeys;
    }

    /**
     * @param $is_default
     * @return $this
     * @author 陈妙威
     */
    public function setIsDefault($is_default)
    {
        $this->isDefault = $is_default;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param $view_uri
     * @return $this
     * @author 陈妙威
     */
    public function setViewURI($view_uri)
    {
        $this->viewURI = $view_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewURI()
    {
        return $this->viewURI;
    }

}
