<?php

namespace orangins\modules\search\phidtype;

use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;

/**
 * Class PhabricatorProfileMenuItemPHIDType
 * @package orangins\modules\search\phidtype
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'PANL';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app",'Profile Menu Item');
    }

    /**
     * @return \orangins\lib\db\ActiveRecord|PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorProfileMenuItemConfiguration();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorSearchApplication::class;
    }

    /**
     * @param PhabricatorObjectQuery $object_query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(PhabricatorObjectQuery $object_query, array $phids)
    {
        return PhabricatorProfileMenuItemConfiguration::find()
            ->withPHIDs($phids);
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(PhabricatorHandleQuery $query,
                                array $handles,
                                array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $config = $objects[$phid];

            $handle->setName(\Yii::t("app",'Profile Menu Item'));
        }
    }
}
