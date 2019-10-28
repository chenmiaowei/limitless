<?php

namespace orangins\modules\herald\phid;

use orangins\modules\herald\query\HeraldRuleQuery;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
* Class HeraldRuleQueryPHIDType
* @package orangins\modules\herald\phid
* @author 陈妙威
*/
class HeraldRulePHIDType extends \orangins\modules\phid\PhabricatorPHIDType
{
    /**
    *
    */
    const TYPECONST = "HRUL";
    /**
    * @return mixed
    */
    public function getTypeName()
    {
        return \Yii::t("app", "herald_rule");
    }

    /**
    * Get the class name for the application this type belongs to.
    *
    * @return string|null Class name of the corresponding application, or null
    *   if the type is not bound to an application.
    */
    public function getPHIDTypeApplicationClass()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::class;
    }

    /**
    * @param $query
    * @param array $phids
    * @return \orangins\modules\herald\query\HeraldRuleQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    public function buildQuery($query, array $phids)
    {
        return HeraldRule::find()->where(['IN', 'phid', $phids]);
    }

    /**
    * Populate provided handles with application-specific data, like titles and
    * URIs.
    *
    * NOTE: The `$handles` and `$objects` lists are guaranteed to be nonempty
    * and have the same keys: subclasses are expected to load information only
    * for handles with visible objects.
    *
    * Because of this guarantee, a safe implementation will typically look like*
    *
    *   foreach ($handles as $phid => $handle) {
    *     $object = $objects[$phid];
    *
    *     $handle->setStuff($object->getStuff());
    *     // ...
    *   }
    *
    * In general, an implementation should call `setName()` and `setURI()` on
    * each handle at a minimum. See @{class:PhabricatorObjectHandle} for other
    * handle properties.
    *
    * @param PhabricatorHandleQuery $query Issuing query object.
    * @param PhabricatorObjectHandle[]   Handles to populate with data.
    * @param HeraldRuleQuery[]    $objects                Objects for these PHIDs loaded by
    *                                        @{method:buildQueryForObjects()}.
    * @return void
    */
    public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $file = $objects[$phid];
            $id = $file->getID();
            $name = $file->id;
            $handle->setName("{$id}");
            $handle->setFullName("{$id}");
        }
    }

    /**
    * @return \orangins\modules\herald\models\HeraldRule
    * @author 陈妙威
    */
    public function newObject()
    {
        return new HeraldRule();
    }

    /**
    * @param PhabricatorObjectQuery $query
    * @param array $phids
    * @return \orangins\modules\herald\query\HeraldRuleQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {
        return HeraldRule::find()->withPHIDs($phids);
    }
}

