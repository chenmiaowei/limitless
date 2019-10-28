<?php

namespace orangins\modules\herald\field;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\contentsource\PhabricatorWebContentSource;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\value\HeraldSelectFieldValue;

/**
 * Class HeraldContentSourceField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldContentSourceField extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'contentsource';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Content source');
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return HeraldEditFieldGroup::FIELDGROUPKEY;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return $this->getAdapter()->getContentSource()->getSource();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHeraldFieldConditions()
    {
        return array(
            HeraldAdapter::CONDITION_IS,
            HeraldAdapter::CONDITION_IS_NOT,
        );
    }

    /**
     * @param $condition
     * @return \orangins\modules\herald\value\HeraldEmptyFieldValue|\orangins\modules\herald\value\HeraldTextFieldValue|\orangins\modules\herald\value\HeraldTokenizerFieldValue
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getHeraldFieldValueType($condition)
    {
        $map = PhabricatorContentSource::getAllContentSources();
        $map = mpull($map, 'getSourceName');
        asort($map);

        return (new HeraldSelectFieldValue())
            ->setKey(self::FIELDCONST)
            ->setDefault(PhabricatorWebContentSource::SOURCECONST)
            ->setOptions($map);
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
