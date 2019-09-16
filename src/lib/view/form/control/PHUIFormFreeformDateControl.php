<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class PHUIFormFreeformDateControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PHUIFormFreeformDateControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-text';
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
                'type' => 'text',
                'name' => $this->getName(),
                'value' => $this->getValue(),
                'disabled' => $this->getDisabled() ? 'disabled' : null,
                'id' => $this->getID(),
            ));
    }

}
