<?php

namespace orangins\modules\auth\phid;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\people\models\PhabricatorAuthInvite;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorAuthInvitePHIDType
 * @package orangins\modules\auth\phid
 * @author 陈妙威
 */
final class PhabricatorAuthInvitePHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'AINV';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Auth Invite');
    }

    /**
     * @return null|PhabricatorAuthInvite
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorAuthInvite();
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
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|void
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {
        throw new PhutilMethodNotImplementedException();
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
            $invite = $objects[$phid];
        }
    }

}
