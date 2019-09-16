<?php

namespace orangins\modules\auth\phid;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorAuthPasswordPHIDType
 * @package orangins\modules\auth\phid
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'APAS';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Auth Password');
    }

    /**
     * @return null|PhabricatorAuthPassword
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorAuthPassword();
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
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\auth\query\PhabricatorAuthPasswordQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {
        return PhabricatorAuthPassword::find()
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
            $password = $objects[$phid];
        }
    }

}
