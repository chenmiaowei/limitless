<?php

namespace orangins\modules\auth\phid;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorAuthSSHKeyPHIDType
 * @package orangins\modules\auth\phid
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'AKEY';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Public SSH Key');
    }

    /**
     * @return null|PhabricatorAuthSSHKey
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorAuthSSHKey();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorAuthApplication::class;
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorAuthSSHKey::find()
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
            $key = $objects[$phid];
            $handle->setName(\Yii::t("app", 'SSH Key %d', $key->getID()));

            if (!$key->getIsActive()) {
                $handle->setStatus(PhabricatorObjectHandle::STATUS_CLOSED);
            }
        }
    }

}
