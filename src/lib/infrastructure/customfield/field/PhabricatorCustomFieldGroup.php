<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019-11-04
 * Time: 14:38
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\infrastructure\customfield\field;


use orangins\lib\OranginsObject;
use PhutilSortVector;

/**
 * Class PhabricatorCustomFieldGroup
 * @package orangins\lib\infrastructure\customfield\field
 * @author 陈妙威
 */
class PhabricatorCustomFieldGroup extends OranginsObject
{
    /**
     * @var
     */
    public $sortOrder;

    /**
     * @var
     */
    public $name;

    /**
     * @var PhabricatorCustomField[]
     */
    public $fields;
    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param mixed $sortOrder
     * @return self
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param PhabricatorCustomField $field
     * @return self
     * @throws \Exception
     */
    public function addField($field)
    {
        $this->fields[] = $field;
        $this->fields = msortv($this->fields, 'getOrderVector');
        return $this;
    }

    /**
     * @return PhabricatorCustomField[]
     */
    public function getFields()
    {
        return $this->fields;
    }



    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOrderVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getSortOrder());
    }
}