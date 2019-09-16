<?php

namespace orangins\modules\transactions\edittype;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDParameterType;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorPHIDListEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
abstract class PhabricatorPHIDListEditType extends PhabricatorEditType
{

    /**
     * @var
     */
    private $datasource;
    /**
     * @var
     */
    private $isSingleValue;
    /**
     * @var
     */
    private $defaultValue;
    /**
     * @var
     */
    private $isNullable;

    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return $this
     * @author 陈妙威
     */
    public function setDatasource(PhabricatorTypeaheadDatasource $datasource)
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param $is_single_value
     * @return $this
     * @author 陈妙威
     */
    public function setIsSingleValue($is_single_value)
    {
        $this->isSingleValue = $is_single_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSingleValue()
    {
        return $this->isSingleValue;
    }

    /**
     * @param array $default_value
     * @return $this
     * @author 陈妙威
     */
    public function setDefaultValue(array $default_value)
    {
        $this->defaultValue = $default_value;
        return $this;
    }

    /**
     * @param $is_nullable
     * @return $this
     * @author 陈妙威
     */
    public function setIsNullable($is_nullable)
    {
        $this->isNullable = $is_nullable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return ConduitPHIDListParameterType|ConduitPHIDParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        $default = parent::newConduitParameterType();
        if ($default) {
            return $default;
        }

        if ($this->getIsSingleValue()) {
            return (new ConduitPHIDParameterType())
                ->setIsNullable($this->getIsNullable());
        } else {
            return new ConduitPHIDListParameterType();
        }
    }

    /**
     * @param $value
     * @return mixed|null
     * @author 陈妙威
     */
    public function getTransactionValueFromBulkEdit($value)
    {
        if (!$this->getIsSingleValue()) {
            return $value;
        }

        if ($value) {
            return head($value);
        }

        return null;
    }

}
