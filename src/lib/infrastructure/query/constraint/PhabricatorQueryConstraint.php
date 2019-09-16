<?php

namespace orangins\lib\infrastructure\query\constraint;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorQueryConstraint
 * @package orangins\lib\infrastructure\query\constraint
 * @author 陈妙威
 */
final class PhabricatorQueryConstraint extends OranginsObject
{

    /**
     *
     */
    const OPERATOR_AND = 'and';
    /**
     *
     */
    const OPERATOR_OR = 'or';
    /**
     *
     */
    const OPERATOR_NOT = 'not';
    /**
     *
     */
    const OPERATOR_NULL = 'null';
    /**
     *
     */
    const OPERATOR_ANCESTOR = 'ancestor';
    /**
     *
     */
    const OPERATOR_EMPTY = 'empty';
    /**
     *
     */
    const OPERATOR_ONLY = 'only';
    /**
     *
     */
    const OPERATOR_ANY = 'any';

    /**
     * @var
     */
    private $operator;
    /**
     * @var
     */
    private $value;

    /**
     * PhabricatorQueryConstraint constructor.
     * @param $operator
     * @param $value
     */
    public function __construct($operator, $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @param $operator
     * @return $this
     * @author 陈妙威
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOperator()
    {
        return $this->operator;
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

}
