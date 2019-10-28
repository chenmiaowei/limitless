<?php

namespace orangins\modules\herald\field;

use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\value\HeraldEmptyFieldValue;

/**
 * Class HeraldAlwaysField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldAlwaysField extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'always';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Always');
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return HeraldBasicFieldGroup::FIELDGROUPKEY;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHeraldFieldConditions()
    {
        return array(
            HeraldAdapter::CONDITION_UNCONDITIONALLY,
        );
    }

    /**
     * @param $condition
     * @return HeraldEmptyFieldValue|\orangins\modules\herald\value\HeraldTextFieldValue|\orangins\modules\herald\value\HeraldTokenizerFieldValue
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        return new HeraldEmptyFieldValue();
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return true;
    }

}
