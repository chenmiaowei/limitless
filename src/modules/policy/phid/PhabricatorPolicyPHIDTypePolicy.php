<?php

namespace orangins\modules\policy\phid;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\application\PhabricatorPolicyApplication;
use orangins\modules\policy\models\PhabricatorPolicy;
use yii\db\Query;

/**
 * Class PhabricatorPolicyPHIDTypePolicy
 * @package orangins\modules\policy\phid
 * @author 陈妙威
 */
final class PhabricatorPolicyPHIDTypePolicy extends PhabricatorPHIDType
{
    /**
     *
     */
    const TYPECONST = 'PLCY';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Policy');
    }


    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorPolicyApplication::class;
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
            $policy = $objects[$phid];

            $handle->setName($policy->getName());
            $handle->setURI($policy->getHref());
        }
    }

    /**
     * @param $query
     * @param array $phids
     * @return Query
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function buildQuery($query, array $phids)
    {
        return PhabricatorUser::find()->where(['IN', 'phid', $phids]);
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {

        return PhabricatorPolicy::find()
            ->withPHIDs($phids);
    }
}
