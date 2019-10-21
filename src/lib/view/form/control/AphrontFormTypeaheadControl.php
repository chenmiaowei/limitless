<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontFormTypeaheadControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormTypeaheadControl extends AphrontFormControl
{

    /**
     * @var
     */
    private $hardpointID;
    /**
     * @var
     */
    private $placeholder;

    /**
     * @param $hardpoint_id
     * @return $this
     * @author 陈妙威
     */
    public function setHardpointID($hardpoint_id)
    {
        $this->hardpointID = $hardpoint_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHardpointID()
    {
        return $this->hardpointID;
    }

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
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-typeahead';
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'style' => 'position: relative;',
                'id' => $this->getHardpointID(),
            ),
            JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'text',
                    'name' => $this->getName(),
                    'value' => $this->getValue(),
                    'placeholder' => $this->placeholder,
                    'disabled' => $this->getDisabled() ? 'disabled' : null,
                    'autocomplete' => 'off',
                    'id' => $this->getID(),
                )));
    }

}
