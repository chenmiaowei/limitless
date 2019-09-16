<?php

namespace orangins\lib\view\form\control;

/**
 * Class AphrontFormMarkupControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormMarkupControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-markup';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return $this->getValue();
    }
}
