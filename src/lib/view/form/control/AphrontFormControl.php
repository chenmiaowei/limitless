<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\AphrontView;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontFormControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
abstract class AphrontFormControl extends AphrontView
{
    /**
     * @var
     */
    private $id;

    /**
     * @var
     */
    private $label;
    /**
     * @var
     */
    private $caption;
    /**
     * @var
     */
    private $error;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $readOnly;
    /**
     * @var
     */
    private $controlID;
    /**
     * @var
     */
    private $controlStyle;
    /**
     * @var
     */
    private $required;
    /**
     * @var
     */
    private $hidden;
    /**
     * @var
     */
    private $classes;

    /**
     * @var
     */
    private $inputClasses;


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
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @param $hidden
     * @return $this
     * @author 陈妙威
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @param $control_id
     * @return $this
     * @author 陈妙威
     */
    public function setControlID($control_id)
    {
        $this->controlID = $control_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlID()
    {
        return $this->controlID;
    }

    /**
     * @param $control_style
     * @return $this
     * @author 陈妙威
     */
    public function setControlStyle($control_style)
    {
        $this->controlStyle = $control_style;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlStyle()
    {
        return $this->controlStyle;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $caption
     * @return $this
     * @author 陈妙威
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param $error
     * @return $this
     * @author 陈妙威
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getError()
    {
        return $this->error;
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
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
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
     * @return bool
     * @author 陈妙威
     */
    public function isValid()
    {
        if ($this->error && $this->error !== true) {
            return false;
        }

        if ($this->isRequired() && $this->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEmpty()
    {
        return !strlen($this->getValue());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSerializedValue()
    {
        return $this->getValue();
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function readSerializedValue($value)
    {
        $this->setValue($value);
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $this->setValue($request->getStr($this->getName()));
        return $this;
    }

    /**
     * @param array $dictionary
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromDictionary(array $dictionary)
    {
        $this->setValue(ArrayHelper::getValue($dictionary, $this->getName()));
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param $readOnly
     * @return $this
     * @author 陈妙威
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function renderInput();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getCustomControlClass();

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldRender()
    {
        return true;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addInputClass($class)
    {
        $this->inputClasses[] = $class;
        return $this;
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function render()
    {
        if (!$this->shouldRender()) {
            return null;
        }

        $custom_class = $this->getCustomControlClass();

        if (strlen($this->getCaption())) {
            $caption = JavelinHtml::tag('div', $this->getCaption(), array('class' => 'form-text text-warning aphront-form-caption'));
        } else {
            $caption = null;
        }

        $inputClasses = $this->inputClasses;
        $inputClasses[] = "col-lg-8 aphront-form-input";
        $input = JavelinHtml::tag('div', [
            $this->renderInput(),
            $caption
        ], array('class' => implode(" ", $inputClasses)));

        $error = null;
        if (strlen($this->getError())) {
            $error = $this->getError();
            if ($error === true) {
                $error = JavelinHtml::tag('span', \Yii::t("app", 'Required'), array('class' => 'text-warning aphront-form-error aphront-form-required'));
            } else {
                $error = JavelinHtml::tag('span', $error, array('class' => 'text-warning aphront-form-error'));
            }
        }

//        $error = JavelinHtml::phutil_tag("div", [
//            "class"=> "col-form-label col-lg-2 text-right"
//        ], $error);

        if (strlen($this->getLabel())) {
            $label = JavelinHtml::tag('label', array(
                $this->getLabel(),
            ), array(
                'class' => 'col-form-label col-lg-2 text-right',
                'for' => $this->getID(),
            ));
        } else {
            $label = JavelinHtml::tag('label', null, array(
                'class' => 'col-form-label col-lg-2',
                'for' => $this->getID(),
            ));;
            $custom_class .= ' aphront-form-control-nolabel';
        }


        $classes = array();
        $classes[] = 'form-group';
        $classes[] = 'mt-2 mb-2';
        $classes[] = 'row';
        $classes[] = $custom_class;
        if ($this->classes) {
            foreach ($this->classes as $class) {
                $classes[] = $class;
            }
        }

        $style = $this->controlStyle;
        if ($this->hidden) {
            $style = 'display: none; ' . $style;
        }

        return JavelinHtml::tag('div',
            array(
                $label,
                $input,
                $error = JavelinHtml::phutil_tag("div", [
                    "class" => "col-form-label col-lg-2 text-left"
                ], $error),
            ),
            array(
                'class' => implode(' ', $classes),
                'id' => $this->controlID,
                'style' => $style,
            ));
    }
}
