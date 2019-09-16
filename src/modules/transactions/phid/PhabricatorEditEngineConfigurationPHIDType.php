<?php

namespace orangins\modules\transactions\phid;

use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;

/**
 * Class PhabricatorEditEngineConfigurationPHIDType
 * @package orangins\modules\transactions\phid
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationPHIDType
    extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'FORM';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app",'Edit Configuration');
    }

    /**
     * @return null|PhabricatorEditEngineConfiguration
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorEditEngineConfiguration();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorTransactionsApplication::className();
    }

    /**
     * @param PhabricatorObjectQuery $object_query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $object_query,
        array $phids)
    {
        return PhabricatorEditEngineConfiguration::find()
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
            $config = $objects[$phid];

            $id = $config->getID();
            $name = $config->getName();

            $handle->setName($name);
            $handle->setURI($config->getURI());
        }
    }

}
