<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringListHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;

/**
 * Class PhabricatorTextAreaEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorTextAreaEditField
    extends PhabricatorEditField
{

    /**
     * @var
     */
    private $monospaced;
    /**
     * @var
     */
    private $height;
    /**
     * @var
     */
    private $isStringList;

    /**
     * @param $monospaced
     * @return $this
     * @author 陈妙威
     */
    public function setMonospaced($monospaced)
    {
        $this->monospaced = $monospaced;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMonospaced()
    {
        return $this->monospaced;
    }

    /**
     * @param $height
     * @return $this
     * @author 陈妙威
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param $is_string_list
     * @return $this
     * @author 陈妙威
     */
    public function setIsStringList($is_string_list)
    {
        $this->isStringList = $is_string_list;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsStringList()
    {
        return $this->isStringList;
    }

    /**
     * @return AphrontFormTextAreaControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        $control = new AphrontFormTextAreaControl();

        if ($this->getMonospaced()) {
            $control->setCustomClass('PhabricatorMonospaced');
        }

        $height = $this->getHeight();
        if ($height) {
            $control->setHeight($height);
        }

        return $control;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        $value = $this->getValue();
        if ($this->getIsStringList()) {
            return implode("\n", $value);
        } else {
            return $value;
        }
    }

    /**
     * @return mixed|ConduitStringListParameterType|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        if ($this->getIsStringList()) {
            return new ConduitStringListParameterType();
        } else {
            return new ConduitStringParameterType();
        }
    }

    /**
     * @return \orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType|AphrontStringHTTPParameterType|AphrontStringListHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        if ($this->getIsStringList()) {
            return new AphrontStringListHTTPParameterType();
        } else {
            return new AphrontStringHTTPParameterType();
        }
    }

}
