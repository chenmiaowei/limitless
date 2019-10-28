<?php

namespace orangins\modules\herald\field\rule;

use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\typeahead\HeraldAdapterDatasource;

/**
 * Class HeraldRuleAdapterField
 * @package orangins\modules\herald\field\rule
 * @author 陈妙威
 */
final class HeraldRuleAdapterField
    extends HeraldRuleField
{

    /**
     *
     */
    const FIELDCONST = 'adapter';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Content type');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return $object->getContentType();
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        return self::STANDARD_PHID;
    }

    /**
     * @return HeraldAdapterDatasource|void
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new HeraldAdapterDatasource();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getDatasourceValueMap()
    {
        $adapters = HeraldAdapter::getAllAdapters();
        return mpull($adapters, 'getAdapterContentName', 'getAdapterContentType');
    }

}
