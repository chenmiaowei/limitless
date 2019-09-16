<?php

namespace orangins\modules\phid;

use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\metamta\query\PhabricatorMetaMTAApplicationEmailQuery;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorMetaMTAApplicationEmailPHIDType
 * @package orangins\modules\phid
 * @author 陈妙威
 */
final class PhabricatorMetaMTAApplicationEmailPHIDType
    extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'APPE';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return pht('Application Email');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTypeIcon()
    {
        return 'fa-email bluegrey';
    }

    /**
     * @return null|PhabricatorMetaMTAApplicationEmail
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorMetaMTAApplicationEmail();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorMetaMTAApplication::className();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return object|\orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorMetaMTAApplicationEmailQuery|\yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorMetaMTAApplicationEmail::find()
            ->withPHIDs($phids);
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $email = $objects[$phid];

            $handle->setName($email->getAddress());
            $handle->setFullName($email->getAddress());
        }
    }
}
