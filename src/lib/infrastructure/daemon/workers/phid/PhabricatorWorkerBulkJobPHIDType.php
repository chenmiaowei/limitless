<?php

namespace orangins\lib\infrastructure\daemon\workers\phid;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorWorkerBulkJobPHIDType
 * @package orangins\lib\infrastructure\daemon\workers\phid
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'BULK';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Bulk Job');
    }

    /**
     * @return null|PhabricatorWorkerBulkJob
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorWorkerBulkJob();
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
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|\orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerBulkJobQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        return PhabricatorWorkerBulkJob::find()
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
            $job = $objects[$phid];

            $id = $job->getID();

            $handle->setName(\Yii::t("app", 'Bulk Job {0}', [$id]));
        }
    }

}
