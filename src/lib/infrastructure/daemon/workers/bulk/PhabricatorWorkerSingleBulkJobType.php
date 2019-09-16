<?php

namespace orangins\lib\infrastructure\daemon\workers\bulk;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;

/**
 * An bulk job which can not be parallelized and executes only one task.
 */
abstract class PhabricatorWorkerSingleBulkJobType
    extends PhabricatorWorkerBulkJobType
{

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed|null
     * @author 陈妙威
     */
    public function getDescriptionForConfirm(PhabricatorWorkerBulkJob $job)
    {
        return null;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return int|mixed
     * @author 陈妙威
     */
    public function getJobSize(PhabricatorWorkerBulkJob $job)
    {
        return 1;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return array|mixed
     * @author 陈妙威
     */
    public function createTasks(PhabricatorWorkerBulkJob $job)
    {
        $tasks = array();

        $tasks[] = PhabricatorWorkerBulkTask::initializeNewTask(
            $job,
            $job->getPHID());

        return $tasks;
    }
}
