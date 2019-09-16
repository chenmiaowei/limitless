<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontFormPasswordControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormPasswordControl extends AphrontFormControl
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
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-password';
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'password',
                'name' => $this->getName(),
                'value' => $this->getValue(),
                'class' => 'form-control',
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'autocomplete' => ($this->disableAutocomplete ? 'off' : null),
                'id' => $this->getID(),
            ));
    }
}
