<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldNewObjectField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldNewObjectField extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'new-object';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Is newly created');
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
     * @return bool
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return !$this->getAdapter()->isSingleEventAdapter();
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return $this->getAdapter()->getIsNewObject();
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        return self::STANDARD_BOOL;
    }

}
