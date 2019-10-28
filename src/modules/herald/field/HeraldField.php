<?php

namespace orangins\modules\herald\field;

use Exception;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\value\HeraldEmptyFieldValue;
use orangins\modules\herald\value\HeraldTextFieldValue;
use orangins\modules\herald\value\HeraldTokenizerFieldValue;
use orangins\modules\people\models\PhabricatorUser;
use Phobject;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;

/**
 * Class HeraldField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
abstract class HeraldField extends Phobject
{

    /**
     * @var HeraldAdapter
     */
    private $adapter;

    /**
     *
     */
    const STANDARD_BOOL = 'standard.bool';
    /**
     *
     */
    const STANDARD_TEXT = 'standard.text';
    /**
     *
     */
    const STANDARD_TEXT_LIST = 'standard.text.list';
    /**
     *
     */
    const STANDARD_TEXT_MAP = 'standard.text.map';
    /**
     *
     */
    const STANDARD_PHID = 'standard.phid';
    /**
     *
     */
    const STANDARD_PHID_LIST = 'standard.phid.list';
    /**
     *
     */
    const STANDARD_PHID_BOOL = 'standard.phid.bool';
    /**
     *
     */
    const STANDARD_PHID_NULLABLE = 'standard.phid.nullable';

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getHeraldFieldName();

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getHeraldFieldValue($object);

    /**
     * @return |null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredAdapterStates()
    {
        return array();
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    protected function getDatasourceValueMap()
    {
        return null;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getHeraldFieldConditions()
    {
        $standard_type = $this->getHeraldFieldStandardType();
        switch ($standard_type) {
            case self::STANDARD_BOOL:
                return array(
                    HeraldAdapter::CONDITION_IS_TRUE,
                    HeraldAdapter::CONDITION_IS_FALSE,
                );
            case self::STANDARD_TEXT:
                return array(
                    HeraldAdapter::CONDITION_CONTAINS,
                    HeraldAdapter::CONDITION_NOT_CONTAINS,
                    HeraldAdapter::CONDITION_IS,
                    HeraldAdapter::CONDITION_IS_NOT,
                    HeraldAdapter::CONDITION_REGEXP,
                    HeraldAdapter::CONDITION_NOT_REGEXP,
                );
            case self::STANDARD_PHID:
                return array(
                    HeraldAdapter::CONDITION_IS_ANY,
                    HeraldAdapter::CONDITION_IS_NOT_ANY,
                );
            case self::STANDARD_PHID_LIST:
                return array(
                    HeraldAdapter::CONDITION_INCLUDE_ALL,
                    HeraldAdapter::CONDITION_INCLUDE_ANY,
                    HeraldAdapter::CONDITION_INCLUDE_NONE,
                    HeraldAdapter::CONDITION_EXISTS,
                    HeraldAdapter::CONDITION_NOT_EXISTS,
                );
            case self::STANDARD_PHID_BOOL:
                return array(
                    HeraldAdapter::CONDITION_EXISTS,
                    HeraldAdapter::CONDITION_NOT_EXISTS,
                );
            case self::STANDARD_PHID_NULLABLE:
                return array(
                    HeraldAdapter::CONDITION_IS_ANY,
                    HeraldAdapter::CONDITION_IS_NOT_ANY,
                    HeraldAdapter::CONDITION_EXISTS,
                    HeraldAdapter::CONDITION_NOT_EXISTS,
                );
            case self::STANDARD_TEXT_LIST:
                return array(
                    HeraldAdapter::CONDITION_CONTAINS,
                    HeraldAdapter::CONDITION_NOT_CONTAINS,
                    HeraldAdapter::CONDITION_REGEXP,
                    HeraldAdapter::CONDITION_NOT_REGEXP,
                    HeraldAdapter::CONDITION_EXISTS,
                    HeraldAdapter::CONDITION_NOT_EXISTS,
                );
            case self::STANDARD_TEXT_MAP:
                return array(
                    HeraldAdapter::CONDITION_CONTAINS,
                    HeraldAdapter::CONDITION_NOT_CONTAINS,
                    HeraldAdapter::CONDITION_REGEXP,
                    HeraldAdapter::CONDITION_NOT_REGEXP,
                    HeraldAdapter::CONDITION_REGEXP_PAIR,
                );
        }

        throw new Exception(
            pht(
                'Herald field "%s" has unknown standard type "%s".',
                get_class($this),
                $standard_type));
    }

    /**
     * @param $condition
     * @return HeraldEmptyFieldValue|HeraldTextFieldValue|HeraldTokenizerFieldValue
     * @throws Exception
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        $standard_type = $this->getHeraldFieldStandardType();
        switch ($standard_type) {
            case self::STANDARD_BOOL:
            case self::STANDARD_PHID_BOOL:
                return new HeraldEmptyFieldValue();
            case self::STANDARD_TEXT:
            case self::STANDARD_TEXT_LIST:
            case self::STANDARD_TEXT_MAP:
                switch ($condition) {
                    case HeraldAdapter::CONDITION_EXISTS:
                    case HeraldAdapter::CONDITION_NOT_EXISTS:
                        return new HeraldEmptyFieldValue();
                    default:
                        return new HeraldTextFieldValue();
                }
            case self::STANDARD_PHID:
            case self::STANDARD_PHID_NULLABLE:
            case self::STANDARD_PHID_LIST:
                switch ($condition) {
                    case HeraldAdapter::CONDITION_EXISTS:
                    case HeraldAdapter::CONDITION_NOT_EXISTS:
                        return new HeraldEmptyFieldValue();
                    default:
                        $tokenizer = (new HeraldTokenizerFieldValue())
                            ->setKey($this->getHeraldFieldName())
                            ->setDatasource($this->getDatasource());

                        $value_map = $this->getDatasourceValueMap();
                        if ($value_map !== null) {
                            $tokenizer->setValueMap($value_map);
                        }

                        return $tokenizer;
                }
                break;

        }

        throw new Exception(
            pht(
                'Herald field "%s" has unknown standard type "%s".',
                get_class($this),
                $standard_type));
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject($object);

    /**
     * @param $object
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getFieldsForObject($object)
    {
        return array($this->getFieldConstant() => $this);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $condition
     * @param $value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function renderConditionValue(
        PhabricatorUser $viewer,
        $condition,
        $value)
    {

        $value_type = $this->getHeraldFieldValueType($condition);
        $value_type->setViewer($viewer);
        return $value_type->renderFieldValue($value);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $condition
     * @param $value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getEditorValue(
        PhabricatorUser $viewer,
        $condition,
        $value)
    {

        $value_type = $this->getHeraldFieldValueType($condition);
        $value_type->setViewer($viewer);
        return $value_type->renderEditorValue($value);
    }

    /**
     * @param HeraldAdapter $adapter
     * @return $this
     * @author 陈妙威
     */
    final public function setAdapter(HeraldAdapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return HeraldAdapter
     * @author 陈妙威
     */
    final public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getFieldConstant()
    {
        return $this->getPhobjectClassConstant(
            'FIELDCONST',
            self::getFieldConstantByteLimit());
    }

    /**
     * @return int
     * @author 陈妙威
     */
    final public static function getFieldConstantByteLimit()
    {
        return 64;
    }

    /**
     * @return HeraldField[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllFields()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getFieldConstant')
            ->execute();
    }

}
