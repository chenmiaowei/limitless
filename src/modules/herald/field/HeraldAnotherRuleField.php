<?php

namespace orangins\modules\herald\field;

use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\value\HeraldSelectFieldValue;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class HeraldAnotherRuleField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldAnotherRuleField extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'rule';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Another Herald rule');
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
    public function supportsObject($object)
    {
        return true;
    }

    /**
     * @param $object
     * @return mixed|null
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHeraldFieldConditions()
    {
        return array(
            HeraldAdapter::CONDITION_RULE,
            HeraldAdapter::CONDITION_NOT_RULE,
        );
    }

    /**
     * @param $condition
     * @return \orangins\modules\herald\value\HeraldEmptyFieldValue|HeraldSelectFieldValue|\orangins\modules\herald\value\HeraldTextFieldValue|\orangins\modules\herald\value\HeraldTokenizerFieldValue
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        // NOTE: This is a bit magical because we don't currently have a reasonable
        // way to populate it from here.
        return (new HeraldSelectFieldValue())
            ->setKey(self::FIELDCONST)
            ->setOptions(array());
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $condition
     * @param $value
     * @return mixed|\orangins\modules\phid\view\PHUIHandleListView
     * @author 陈妙威
     */
    public function renderConditionValue(
        PhabricatorUser $viewer,
        $condition,
        $value)
    {

        $value = (array)$value;

        return $viewer->renderHandleList($value);
    }

}
