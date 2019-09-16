<?php

namespace orangins\lib\infrastructure\daemon\workers\bulk;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;
use orangins\lib\OranginsObject;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorWorkerBulkJobType
 * @package orangins\lib\infrastructure\daemon\workers\bulk
 * @author 陈妙威
 */
abstract class PhabricatorWorkerBulkJobType extends OranginsObject
{

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getJobName(PhabricatorWorkerBulkJob $job);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getBulkJobTypeKey();

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getJobSize(PhabricatorWorkerBulkJob $job);

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDescriptionForConfirm(
        PhabricatorWorkerBulkJob $job);

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @author 陈妙威
     */
    abstract public function createTasks(PhabricatorWorkerBulkJob $job);

    /**
     * @param PhabricatorUser $actor
     * @param PhabricatorWorkerBulkJob $job
     * @param PhabricatorWorkerBulkTask $task
     * @return mixed
     * @author 陈妙威
     */
    abstract public function runTask(
        PhabricatorUser $actor,
        PhabricatorWorkerBulkJob $job,
        PhabricatorWorkerBulkTask $task);

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return string
     * @author 陈妙威
     */
    public function getDoneURI(PhabricatorWorkerBulkJob $job)
    {
        return $job->getManageURI();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllJobTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getBulkJobTypeKey')
            ->execute();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorWorkerBulkJob $job
     * @return array
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getCurtainActions(
        PhabricatorUser $viewer,
        PhabricatorWorkerBulkJob $job)
    {

        if ($job->isConfirming()) {
            $continue_uri = $job->getMonitorURI();
        } else {
            $continue_uri = $job->getDoneURI();
        }

        $continue = (new PhabricatorActionView())
            ->setHref($continue_uri)
            ->setIcon('fa-arrow-circle-o-right')
            ->setName(\Yii::t("app", 'Continue'));

        return array(
            $continue,
        );
    }

}
