<?php

namespace orangins\lib\view\form\control;

/**
 * Class AphrontFormStaticControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormStaticControl extends AphrontFormControl
{
    public function init()
    {
        parent::init();
        $this->addInputClass("mt-2");
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-static';
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
