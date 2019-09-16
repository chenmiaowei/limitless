<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUI;
use orangins\modules\widgets\javelin\JavelinUniformControlAsset;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontFormCheckboxControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormCheckboxControl extends AphrontFormControl
{

    /**
     * @var array
     */
    private $boxes = array();
    /**
     * @var
     */
    private $checkboxKey;

    /**
     * @param $checkbox_key
     * @return $this
     * @author 陈妙威
     */
    public function setCheckboxKey($checkbox_key)
    {
        $this->checkboxKey = $checkbox_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCheckboxKey()
    {
        return $this->checkboxKey;
    }

    /**
     * @param $name
     * @param $value
     * @param $label
     * @param bool $checked
     * @param null $id
     * @return $this
     * @author 陈妙威
     */
    public function addCheckbox(
        $name,
        $value,
        $label,
        $checked = false,
        $id = null)
    {
        $this->boxes[] = array(
            'name' => $name,
            'value' => $value,
            'label' => $label,
            'checked' => $checked,
            'id' => $id,
        );
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-checkbox';
    }

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setOptions(array $options)
    {
        $boxes = array();
        foreach ($options as $key => $value) {
            $boxes[] = array(
                'value' => $key,
                'label' => $value,
            );
        }

        $this->boxes = $boxes;

        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    protected function renderInput()
    {
        $rows = array();
        foreach ($this->boxes as $box) {
            $id = ArrayHelper::getValue($box, 'id');
            if ($id === null) {
                $id = JavelinHtml::generateUniqueNodeId();
            }

            JavelinHtml::initBehavior(new JavelinUniformControlAsset(), [
                'id' => $id
            ]);

            $name = ArrayHelper::getValue($box, 'name');
            if ($name === null) {
                $name = $this->getName() . '[]';
            }

            $value = $box['value'];

            if (array_key_exists('checked', $box)) {
                $checked = $box['checked'];
            } else {
                $checked = in_array($value, $this->getValue());
            }

            $checkbox = JavelinHtml::phutil_tag(
                'input',
                array(
                    'id' => $id,
                    'type' => 'checkbox',
                    'class' => 'form-check-input-styled',
                    'name' => $name,
                    'data-fouc' => '',
                    'value' => $box['value'],
                    'checked' => $checked ? 'checked' : null,
                    'disabled' => $this->getDisabled() ? 'disabled' : null,
                ));
            $content = JavelinHtml::phutil_tag("label", [
                "class" => "form-check-label"
            ], array(
                $checkbox,
                $box['label']
            ));


            $rows[] = JavelinHtml::phutil_tag('div', array(
                'class' => 'form-check'
            ), $content);
        }

        // When a user submits a form with a checkbox unchecked, the browser
        // doesn't submit anything to the server. This hidden key lets the server
        // know that the checkboxes were present on the client, the user just did
        // not select any of them.

        $checkbox_key = $this->getCheckboxKey();
        if ($checkbox_key) {
            $rows[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $checkbox_key,
                    'value' => 1,
                ));
        }

        return $rows;
    }

}
