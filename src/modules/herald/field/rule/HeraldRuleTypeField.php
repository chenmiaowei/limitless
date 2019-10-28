<?php

namespace orangins\modules\herald\field\rule;

use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\typeahead\HeraldRuleTypeDatasource;

/**
 * Class HeraldRuleTypeField
 * @package orangins\modules\herald\field\rule
 * @author 陈妙威
 */
final class HeraldRuleTypeField
    extends HeraldRuleField
{

    /**
     *
     */
    const FIELDCONST = 'rule-type';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Rule type');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return $object->getRuleType();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        return self::STANDARD_PHID;
    }

    /**
     * @return HeraldRuleTypeDatasource
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new HeraldRuleTypeDatasource();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getDatasourceValueMap()
    {
        return HeraldRuleTypeConfig::getRuleTypeMap();
    }

}
