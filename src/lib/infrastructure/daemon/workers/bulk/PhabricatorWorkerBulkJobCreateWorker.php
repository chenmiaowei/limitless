<?php

namespace orangins\lib\infrastructure\daemon\workers\bulk;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;

/**
 * Class PhabricatorWorkerBulkJobCreateWorker
 * @package orangins\lib\infrastructure\daemon\workers\bulk
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobCreateWorker
    extends PhabricatorWorkerBulkJobWorker
{

    /**
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \AphrontCountQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function doWork()
    {
        $lock = $this->acquireJobLock();

        $job = $this->loadJob();
        $actor = $this->loadActor($job);

        $status = $job->getStatus();
        switch ($status) {
            case PhabricatorWorkerBulkJob::STATUS_WAITING:
                // This is what we expect. Other statuses indicate some kind of race
                // is afoot.
                break;
            default:
                throw new PhabricatorWorkerPermanentFailureException(
                    \Yii::t("app",
                        'Found unexpected job status ("{0}").',
                        [
                            $status
                        ]));
        }

        $tasks = $job->createTasks();
        foreach ($tasks as $task) {
            $task->save();
        }

        $this->updateJobStatus(
            $job,
            PhabricatorWorkerBulkJob::STATUS_RUNNING);

        $lock->unlock();

        foreach ($tasks as $task) {
            PhabricatorWorker::scheduleTask(
                'PhabricatorWorkerBulkJobTaskWorker',
                array(
                    'jobID' => $job->getID(),
                    'taskID' => $task->getID(),
                ),
                array(
                    'priority' => PhabricatorWorker::PRIORITY_BULK,
                ));
        }

        $this->updateJob($job);
    }

}
