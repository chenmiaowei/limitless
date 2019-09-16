<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/29
 * Time: 10:41 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\form\control;



use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontFormTextControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
class AphrontFormEmptyControl extends AphrontFormControl
{
    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-empty';
    }

    /**
     * @return null|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function render() {
        $classes = array();
        $classes[] = 'form-group';
        $classes[] = 'mt-2 mb-2';
        $classes[] = 'row';
        $classes[] = $this->getCustomControlClass();
        return JavelinHtml::phutil_tag("div", [
            'class' => implode(' ', $classes),
            'id' => $this->getID()
        ]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function renderInput()
    {
        return '';
    }
}