<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\field\HeraldEditFieldGroup;
use orangins\modules\herald\field\HeraldField;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAApplicationEmailDatasource;

/**
 * Class PhabricatorMetaMTAApplicationEmailHeraldField
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailHeraldField
    extends HeraldField
{

    /**
     *
     */
    const FIELDCONST = 'application-email';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Receiving email addresses');
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
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return $this->getAdapter()->supportsApplicationEmail();
    }

    /**
     * @param $object
     * @return array|mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        $phids = array();

        $email = $this->getAdapter()->getApplicationEmail();
        if ($email) {
            $phids[] = $email->getPHID();
        }

        return $phids;
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        return self::STANDARD_PHID_LIST;
    }

    /**
     * @return PhabricatorMetaMTAApplicationEmailDatasource|void
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new PhabricatorMetaMTAApplicationEmailDatasource();
    }

}
