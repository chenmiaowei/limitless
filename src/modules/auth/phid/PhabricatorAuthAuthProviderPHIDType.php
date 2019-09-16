<?php

namespace orangins\modules\auth\phid;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorAuthAuthProviderPHIDType
 * @package orangins\modules\auth\phid
 * @author 陈妙威
 */
final class PhabricatorAuthAuthProviderPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'AUTH';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Auth Provider');
    }

    /**
     * @return PhabricatorAuthProviderConfig|\orangins\lib\db\ActiveRecord
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorAuthProviderConfig();
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
     * @return \orangins\modules\auth\query\PhabricatorAuthProviderConfigQuery|\orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|object
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorAuthProviderConfig::find()
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
            $provider = $objects[$phid]->getProvider();

            if ($provider) {
                $handle->setName($provider->getProviderName());
            }
        }
    }

}
