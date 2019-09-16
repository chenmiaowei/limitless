<?php

namespace orangins\lib\infrastructure\daemon\workers\bulk;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;

/**
 * Class PhabricatorWorkerBulkJobTaskWorker
 * @package orangins\lib\infrastructure\daemon\workers\bulk
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobTaskWorker
    extends PhabricatorWorkerBulkJobWorker
{

    /**
     * @return mixed|void
     * @throws PhabricatorWorkerPermanentFailureException
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
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function doWork()
    {
        $lock = $this->acquireTaskLock();

        $task = $this->loadTask();
        $status = $task->getStatus();
        switch ($task->getStatus()) {
            case PhabricatorWorkerBulkTask::STATUS_WAITING:
                // This is what we expect.
                break;
            default:
                throw new PhabricatorWorkerPermanentFailureException(
                    \Yii::t("app",
                        'Found unexpected task status ("{0}").',
                        [
                            $status
                        ]));
        }

        $task
            ->setStatus(PhabricatorWorkerBulkTask::STATUS_RUNNING)
            ->save();

        $lock->unlock();

        $job = $this->loadJob();
        $actor = $this->loadActor($job);

        try {
            $job->runTask($actor, $task);
            $status = PhabricatorWorkerBulkTask::STATUS_DONE;
        } catch (\Exception $ex) {
            phlog($ex);
            $status = PhabricatorWorkerBulkTask::STATUS_FAIL;
        }

        $task
            ->setStatus($status)
            ->save();

        $this->updateJob($job);
    }

}
