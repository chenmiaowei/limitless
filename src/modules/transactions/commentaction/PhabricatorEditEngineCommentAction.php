<?php

namespace orangins\modules\transactions\commentaction;

use orangins\lib\OranginsObject;
use PhutilSortVector;

/**
 * Class PhabricatorEditEngineCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
abstract class PhabricatorEditEngineCommentAction extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $label;
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $initialValue;
    /**
     * @var
     */
    private $order;
    /**
     * @var
     */
    private $groupKey;
    /**
     * @var
     */
    private $conflictKey;
    /**
     * @var
     */
    private $submitButtonText;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPHUIXControlType();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPHUIXControlSpecification();

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $group_key
     * @return $this
     * @author 陈妙威
     */
    public function setGroupKey($group_key)
    {
        $this->groupKey = $group_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGroupKey()
    {
        return $this->groupKey;
    }

    /**
     * @param $conflict_key
     * @return $this
     * @author 陈妙威
     */
    public function setConflictKey($conflict_key)
    {
        $this->conflictKey = $conflict_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConflictKey()
    {
        return $this->conflictKey;
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
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSortVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getOrder());
    }

    /**
     * @param $initial_value
     * @return $this
     * @author 陈妙威
     */
    public function setInitialValue($initial_value)
    {
        $this->initialValue = $initial_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitButtonText($text)
    {
        $this->submitButtonText = $text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitButtonText()
    {
        return $this->submitButtonText;
    }

}
