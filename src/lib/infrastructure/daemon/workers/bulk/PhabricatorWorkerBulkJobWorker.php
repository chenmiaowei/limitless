<?php

namespace orangins\lib\infrastructure\daemon\workers\bulk;

use orangins\lib\infrastructure\daemon\workers\editor\PhabricatorWorkerBulkJobEditor;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;
use orangins\lib\infrastructure\util\PhabricatorGlobalLock;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorWorkerBulkJobWorker
 * @package orangins\lib\infrastructure\daemon\workers\bulk
 * @author 陈妙威
 */
abstract class PhabricatorWorkerBulkJobWorker
    extends PhabricatorWorker
{

    /**
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function acquireJobLock()
    {
        return PhabricatorGlobalLock::newLock('bulkjob.' . $this->getJobID())->lock(15);
    }

    /**
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function acquireTaskLock()
    {
        return PhabricatorGlobalLock::newLock('bulktask.' . $this->getTaskID())->lock(15);
    }

    /**
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @author 陈妙威
     */
    final protected function getJobID()
    {
        $data = $this->getTaskData();
        $id = ArrayHelper::getValue($data, 'jobID');
        if (!$id) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Worker has no job ID.'));
        }
        return $id;
    }

    /**
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @author 陈妙威
     */
    final protected function getTaskID()
    {
        $data = $this->getTaskData();
        $id = ArrayHelper::getValue($data, 'taskID');
        if (!$id) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Worker has no task ID.'));
        }
        return $id;
    }

    /**
     * @return mixed|null
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function loadJob()
    {
        $id = $this->getJobID();
        $job = PhabricatorWorkerBulkJob::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withIDs(array($id))
            ->executeOne();
        if (!$job) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Worker has invalid job ID ("{0}").', [
                    $id
                ]));
        }
        return $job;
    }

    /**
     * @throws PhabricatorWorkerPermanentFailureException
     * @author 陈妙威
     * @return PhabricatorWorkerBulkTask
     */
    final protected function loadTask()
    {
        $id = $this->getTaskID();
        $task = PhabricatorWorkerBulkTask::findOne($id);
        if (!$task) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Worker has invalid task ID ("{0}").', [
                    $id
                ]));
        }
        return $task;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function loadActor(PhabricatorWorkerBulkJob $job)
    {
        $actor_phid = $job->getAuthorPHID();
        $actor = PhabricatorUser::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($actor_phid))
            ->executeOne();
        if (!$actor) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Worker has invalid actor PHID ("{0}").', [
                    $actor_phid
                ]));
        }

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $actor,
            $job,
            PhabricatorPolicyCapability::CAN_EDIT);

        if (!$can_edit) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Job actor does not have permission to edit job.'));
        }

        // Allow the worker to fill user caches inline; bulk jobs occasionally
        // need to access user preferences.
        $actor->setAllowInlineCacheGeneration(true);

        return $actor;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @throws PhabricatorWorkerPermanentFailureException
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
     * @author 陈妙威
     */
    final protected function updateJob(PhabricatorWorkerBulkJob $job)
    {
        $has_work = $this->hasRemainingWork($job);
        if ($has_work) {
            return;
        }

        $lock = $this->acquireJobLock();

        $job = $this->loadJob();
        if ($job->getStatus() == PhabricatorWorkerBulkJob::STATUS_RUNNING) {
            if (!$this->hasRemainingWork($job)) {
                $this->updateJobStatus(
                    $job,
                    PhabricatorWorkerBulkJob::STATUS_COMPLETE);
            }
        }

        $lock->unlock();
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return bool
     * @author 陈妙威
     */
    private function hasRemainingWork(PhabricatorWorkerBulkJob $job)
    {

        $phabricatorWorkerBulkTask = new PhabricatorWorkerBulkTask();
        return (new Query())
            ->from($phabricatorWorkerBulkTask::tableName())
            ->andWhere([
                'bulk_job_phid' => $job->getPHID()
            ])
            ->andWhere(
                ['NOT IN', 'status', array(
                    PhabricatorWorkerBulkTask::STATUS_DONE,
                    PhabricatorWorkerBulkTask::STATUS_FAIL,
                )]
            )
            ->exists();
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @param $status
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
     * @throws \yii\base\InvalidConfigException*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function updateJobStatus(PhabricatorWorkerBulkJob $job, $status)
    {
        $type_status = PhabricatorWorkerBulkJobTransaction::TYPE_STATUS;

        $xactions = array();
        $xactions[] = (new PhabricatorWorkerBulkJobTransaction())
            ->setTransactionType($type_status)
            ->setNewValue($status);

        $daemon_source = $this->newContentSource();

        $app_phid = (new PhabricatorDaemonsApplication())->getPHID();

        (new PhabricatorWorkerBulkJobEditor())
            ->setActor(PhabricatorUser::getOmnipotentUser())
            ->setActingAsPHID($app_phid)
            ->setContentSource($daemon_source)
            ->setContinueOnMissingFields(true)
            ->applyTransactions($job, $xactions);
    }

}
