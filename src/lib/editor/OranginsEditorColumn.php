<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/4
 * Time: 6:04 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\editor;

use orangins\lib\OranginsObject;

/**
 * Class OranginsEditorAttribute
 * @package orangins\lib\editor
 * @author 陈妙威
 */
class OranginsEditorColumn extends OranginsObject
{
    /**
     *
     */
    const TARGET_TEXT = 'text';
    /**
     *
     */
    const TARGET_HTML = 'html';

    /**
     *
     */
    const TYPE_COLUMN_SAFE = 'safe';
    /**
     *
     */
    const TYPE_COLUMN_STRING = 'string';
    /**
     *
     */
    const TYPE_COLUMN_INT = 'int';
    /**
     * @var string
     */
    public $attribute_name;

    /**
     * @var string
     */
    public $label;

    /**
     * @var
     */
    public $control;

    /**
     * @var
     */
    public $controlOptions = [];

    /**
     * @var
     */
    public $controlInstructions;

    /**
     * @var bool
     */
    public $required = false;

    /**
     * Yii ActiveRecord rule 验证类型
     * @var string
     */
    public $column_type = 'safe';

    /**
     * @var string 类型
     */
    public $transaction_type;

    /**
     * @var bool
     */
    public $hidden = false;


    /**
     * @return string
     */
    public function getControlInstructions()
    {
        return $this->controlInstructions;
    }

    /**
     * @param mixed $controlInstructions
     * @return static
     */
    public function setControlInstructions($controlInstructions)
    {
        $this->controlInstructions = $controlInstructions;
        return $this;
    }


    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     * @return static
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return string
     */
    public function getColumnType()
    {
        return $this->column_type;
    }

    /**
     * @param string $column_type
     * @return static
     */
    public function setColumnType($column_type)
    {
        $this->column_type = $column_type;
        return $this;
    }


    /**
     * @return string
     */
    public function getAttributeName()
    {
        return $this->attribute_name;
    }

    /**
     * @param string $attribute
     * @return OranginsEditorColumn
     */
    public function setAttributeName($attribute)
    {
        $this->attribute_name = $attribute;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getControl()
    {
        return $this->control ? $this->control : InputWidgetControl::class;
    }

    /**
     * @param string $control
     * @param array $controlOptions
     * @return OranginsEditorColumn
     */
    public function setControl($control, $controlOptions = [])
    {
        $this->control = $control;
        $this->controlOptions = $controlOptions;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getControlOptions()
    {
        return $this->controlOptions;
    }

    /**
     * @param mixed $controlOptions
     * @return static
     */
    public function setControlOptions($controlOptions)
    {
        $this->controlOptions = $controlOptions;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transaction_type;
    }

    /**
     * @param string $transaction_type
     * @return OranginsEditorColumn
     */
    public function setTransactionType($transaction_type)
    {
        $this->transaction_type = $transaction_type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     * @return static
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return static
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }
}