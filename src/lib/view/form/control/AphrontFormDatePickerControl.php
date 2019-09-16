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
use orangins\modules\widgets\javelin\JavelinDatepickerAsset;

/**
 * Class AphrontFormTextControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
class AphrontFormDatePickerControl extends AphrontFormControl
{
    /**
     * @var
     */
    private $placeholder;

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
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
        return 'aphront-form-control-datepicker';
    }
    /**
     * @return mixed|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    protected function renderInput()
    {
        $ID = JavelinHtml::generateUniqueNodeId();
        JavelinHtml::initBehavior(new JavelinDatepickerAsset(), [
           'id' => $ID,
        ]);
        return JavelinHtml::input('text', $this->getName(), $this->getValue(), array(
            'disabled' => $this->getDisabled() ? 'disabled' : null,
            'id' => $ID,
            'class' => 'form-control',
            'placeholder' => $this->getPlaceholder(),
        ));
    }
}