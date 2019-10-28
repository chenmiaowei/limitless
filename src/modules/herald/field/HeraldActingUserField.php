<?php

namespace orangins\modules\herald\field;

use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;

/**
 * Class HeraldActingUserField
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldActingUserField
    extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'herald.acting-user';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Acting user');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        $actingAsPHID = $this->getAdapter()->getActingAsPHID();
        return $actingAsPHID;
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
     * @return PhabricatorPeopleDatasource|void
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new PhabricatorPeopleDatasource();
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
     * @return string|null
     * @author 陈妙威
     */
    public function getFieldGroupKey()
    {
        return HeraldEditFieldGroup::FIELDGROUPKEY;
    }

}
