<?php

namespace orangins\lib\infrastructure\daemon\workers\phid;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorWorkerTriggerPHIDType
 * @package orangins\lib\infrastructure\daemon\workers\phid
 * @author 陈妙威
 */
final class PhabricatorWorkerTriggerPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'TRIG';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Trigger');
    }

    /**
     * @return null|PhabricatorWorkerTrigger
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorWorkerTrigger();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorDaemonsApplication::className();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerTriggerQuery|\orangins\lib\infrastructure\query\PhabricatorQuery|\orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorWorkerTrigger::find()
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
            $trigger = $objects[$phid];

            $id = $trigger->getID();

            $handle->setName(\Yii::t("app", 'Trigger %d', $id));
        }
    }

}
