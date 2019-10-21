<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\helpers\JavelinHtml;

/**
 * Class PHUIFormDividerControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PHUIFormDividerControl extends AphrontFormControl
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'phui-form-divider';
    }

    /**
     * @return mixed|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return JavelinHtml::phutil_tag('hr', array());
    }
}
