<?php

namespace orangins\lib\view\form;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class PHUIFormInsetView
 * @package orangins\lib\view\form
 * @author 陈妙威
 */
final class PHUIFormInsetView extends AphrontView
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $rightButton;
    /**
     * @var
     */
    private $content;
    /**
     * @var array
     */
    private $hidden = array();

    /**
     * @var
     */
    private $divAttributes;

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param $button
     * @return $this
     * @author 陈妙威
     */
    public function setRightButton($button)
    {
        $this->rightButton = $button;
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addHiddenInput($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $hidden_key => $hidden_value) {
                $this->hidden[] = array($key . '[' . $hidden_key . ']', $hidden_value);
            }
        } else {
            $this->hidden[] = array($key, $value);
        }
        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     * @author 陈妙威
     */
    public function addDivAttributes(array $attributes)
    {
        $this->divAttributes = $attributes;
        return $this;
    }

    /**
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {

        $right_button = $desc = '';

        $hidden_inputs = array();
        foreach ($this->hidden as $inp) {
            list($key, $value) = $inp;
            $hidden_inputs[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'class' => 'form-control',
                    'name' => $key,
                    'value' => $value,
                ));
        }

        if ($this->rightButton) {
            $right_button = JavelinHtml::phutil_tag(
                'div',
                array(),
                $this->rightButton);
            $right_button = JavelinHtml::phutil_tag_div('text-right grouped', $right_button);
        }

        if ($this->description) {
            $desc = JavelinHtml::phutil_tag(
                'p',
                array(),
                $this->description);
        }

        $div_attributes = $this->divAttributes;
        $classes = array('card border-top-1 border-top-success rounded-top-0 phui-form-inset');
        if (isset($div_attributes['class'])) {
            $classes[] = $div_attributes['class'];
        }

        $div_attributes['class'] = implode(' ', $classes);

        $content = $hidden_inputs;
        $content[] = $right_button;
        $content[] = $desc;


        $header = null;
        if ($this->title != '') {
            $header = JavelinHtml::phutil_tag("div", [
                "class" => "card-header bg-light",
            ], [
                JavelinHtml::phutil_tag('h6', array(
                    "class" => "card-title"
                ), $this->title)
            ]);
        }

        if ($this->content) {
            $content[] = $this->content;
        }

        $content = array_merge($content, $this->renderChildren());

        $content = JavelinHtml::phutil_tag("div", [
            "class" => "card-body"
        ], [
            $content
        ]);
        return JavelinHtml::phutil_tag_div("row", [
            JavelinHtml::phutil_tag_div('col-md-2'),
            JavelinHtml::phutil_tag_div('col-md-8', JavelinHtml::phutil_tag('div', $div_attributes, [
                $header,
                $content
            ]))
        ]);
    }
}
