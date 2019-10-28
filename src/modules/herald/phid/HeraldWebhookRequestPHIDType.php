<?php

namespace orangins\modules\herald\phid;

use orangins\modules\herald\query\HeraldWebhookRequestQuery;
use orangins\modules\herald\models\HeraldWebhookRequest;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
* Class HeraldWebhookRequestQueryPHIDType
* @package orangins\modules\herald\phid
* @author 陈妙威
*/
class HeraldWebhookRequestPHIDType extends \orangins\modules\phid\PhabricatorPHIDType
{
    /**
    *
    */
    const TYPECONST = "HWBR";
    /**
    * @return mixed
    */
    public function getTypeName()
    {
        return \Yii::t("app", "herald_webhookrequest");
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
    * @return \orangins\modules\herald\query\HeraldWebhookRequestQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    public function buildQuery($query, array $phids)
    {
        return HeraldWebhookRequest::find()->where(['IN', 'phid', $phids]);
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
    * @param HeraldWebhookRequestQuery[]    $objects                Objects for these PHIDs loaded by
    *                                        @{method:buildQueryForObjects()}.
    * @return void
    */
    public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $request = $objects[$phid];
            $handle->setName(pht('Webhook Request %d', $request->getID()));
        }
    }

    /**
    * @return \orangins\modules\herald\models\HeraldWebhookRequest
    * @author 陈妙威
    */
    public function newObject()
    {
        return new HeraldWebhookRequest();
    }

    /**
    * @param PhabricatorObjectQuery $query
    * @param array $phids
    * @return \orangins\modules\herald\query\HeraldWebhookRequestQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {
        return HeraldWebhookRequest::find()->withPHIDs($phids);
    }
}

