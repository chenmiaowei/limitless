<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\widgets\javelin\JavelinUniformControlAsset;

/**
 * Class AphrontFormRadioButtonControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormRadioButtonControl extends AphrontFormControl
{

    /**
     * @var array
     */
    private $buttons = array();

    /**
     * @param $value
     * @param $label
     * @param $caption
     * @param null $class
     * @param bool $disabled
     * @return $this
     * @author 陈妙威
     */
    public function addButton(
        $value,
        $label,
        $caption,
        $class = null,
        $disabled = false)
    {
        $this->buttons[] = array(
            'value' => $value,
            'label' => $label,
            'caption' => $caption,
            'class' => $class,
            'disabled' => $disabled,
        );
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-radio';
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $rows = array();
        foreach ($this->buttons as $button) {
            $id = JavelinHtml::generateUniqueNodeId();
            JavelinHtml::initBehavior(new JavelinUniformControlAsset(), [
                'id' => $id
            ]);
            $radio = JavelinHtml::phutil_tag(
                'input',
                array(
                    'id' => $id,
                    'type' => 'radio',
                    'name' => $this->getName(),
                    'value' => $button['value'],
                    'class' => 'form-check-input-styled',
                    'checked' => ($button['value'] == $this->getValue())
                        ? 'checked'
                        : null,
                    'disabled' => ($this->getDisabled() || $button['disabled'])
                        ? 'disabled'
                        : null,
                ));
            $label = $button['label'];

            if ($button['caption']) {
                $label = array(
                    $label,
                    JavelinHtml::phutil_tag_div('text-muted aphront-form-radio-caption', $button['caption']),
                );
            }
            $content = JavelinHtml::phutil_tag("label", [
                "class" => "form-check-label ". $button['class'],
                'for' => $id,
            ], [
                $radio,
                $label
            ]);
            $rows[] = JavelinHtml::phutil_tag('div', array(
                'class' => 'form-check'
            ), $content);
        }

        return JavelinHtml::phutil_tag(
            'div',
            array('class' => 'form-group aphront-form-control-radio-layout'),
            $rows);
    }

}
