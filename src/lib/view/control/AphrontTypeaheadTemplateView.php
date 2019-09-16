<?php

namespace orangins\lib\view\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class AphrontTypeaheadTemplateView
 * @package orangins\lib\view\control
 * @author 陈妙威
 */
final class AphrontTypeaheadTemplateView extends AphrontView
{

    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $id;

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param array $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue(array $value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function render()
    {
        $id = $this->id;
        $name = $this->getName();
        $values = nonempty($this->getValue(), array());

        $tokens = array();
        foreach ($values as $key => $value) {
            $tokens[] = $this->renderToken($key, $value);
        }

        $input = JavelinHtml::phutil_tag(
            'input',
            array(
                'name' => $name,
                'class' => 'jx-typeahead-input',
                'sigil' => 'typeahead',
                'type' => 'text',
                'value' => $this->value,
                'autocomplete' => 'off',
            ));

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $id,
                'sigil' => 'typeahead-hardpoint',
                'class' => 'jx-typeahead-hardpoint',
            ),
            array(
                $input,
                JavelinHtml::phutil_tag('div', array('style' => 'clear: both'), ''),
            ));
    }
}
