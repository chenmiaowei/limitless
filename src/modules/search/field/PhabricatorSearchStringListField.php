<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;

/**
 * Class PhabricatorSearchStringListField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchStringListField
    extends PhabricatorSearchField
{

    /**
     * @var
     */
    private $placeholder;

    /**
     * @param $placeholder
     * @return $this
     * @author 陈妙威
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return array();
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array|array[]|false|mixed|null|string|string[]
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $request->getStrList($key);
    }

    /**
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        $control = new AphrontFormTextControl();

        $placeholder = $this->getPlaceholder();
        if ($placeholder !== null) {
            $control->setPlaceholder($placeholder);
        }

        return $control;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        return implode(', ', parent::getValueForControl());
    }

    /**
     * @return null|ConduitStringListParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringListParameterType();
    }

}
