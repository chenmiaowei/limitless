<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\helpers\JavelinHtml;

/**
 * Class PHUIFormNumberControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PHUIFormNumberControl extends AphrontFormControl
{

    /**
     * @var
     */
    private $disableAutocomplete;

    /**
     * @param $disable_autocomplete
     * @return $this
     * @author 陈妙威
     */
    public function setDisableAutocomplete($disable_autocomplete)
    {
        $this->disableAutocomplete = $disable_autocomplete;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisableAutocomplete()
    {
        return $this->disableAutocomplete;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'phui-form-number';
    }

    /**
     * @return mixed|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        if ($this->getDisableAutocomplete()) {
            $autocomplete = 'off';
        } else {
            $autocomplete = null;
        }

        return JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'text',
                'pattern' => '\d*',
                'name' => $this->getName(),
                'value' => $this->getValue(),
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'autocomplete' => $autocomplete,
                'id' => $this->getID(),
            ));
    }

}
