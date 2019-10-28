<?php

namespace orangins\modules\herald\phid;

use orangins\modules\herald\query\HeraldWebhookQuery;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
* Class HeraldWebhookQueryPHIDType
* @package orangins\modules\herald\phid
* @author 陈妙威
*/
class HeraldWebhookPHIDType extends \orangins\modules\phid\PhabricatorPHIDType
{
    /**
    *
    */
    const TYPECONST = "HWBH";
    /**
    * @return mixed
    */
    public function getTypeName()
    {
        return \Yii::t("app", "herald_webhook");
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
    * @return \orangins\modules\herald\query\HeraldWebhookQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    public function buildQuery($query, array $phids)
    {
        return HeraldWebhook::find()->where(['IN', 'phid', $phids]);
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
    * @param HeraldWebhookQuery[]    $objects                Objects for these PHIDs loaded by
    *                                        @{method:buildQueryForObjects()}.
    * @return void
    */
    public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects)
    {
        foreach ($handles as $phid => $handle) {
            $hook = $objects[$phid];

            $name = $hook->getName();
            $id = $hook->getID();

            $handle
                ->setName($name)
                ->setURI($hook->getURI())
                ->setFullName(pht('Webhook %d %s', $id, $name));

            if ($hook->isDisabled()) {
                $handle->setStatus(PhabricatorObjectHandle::STATUS_CLOSED);
            }
        }
    }

    /**
    * @return \orangins\modules\herald\models\HeraldWebhook
    * @author 陈妙威
    */
    public function newObject()
    {
        return new HeraldWebhook();
    }

    /**
    * @param PhabricatorObjectQuery $query
    * @param array $phids
    * @return \orangins\modules\herald\query\HeraldWebhookQuery
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {
        return HeraldWebhook::find()->withPHIDs($phids);
    }
}

