<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerYieldException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerActiveTaskQuery;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerLeaseQuery;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use Throwable;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "worker_activetask".
 *
 * @property int $id
 * @property string $task_class
 * @property string $lease_owner
 * @property int $lease_expires
 * @property int $failure_count
 * @property int $data_id
 * @property int $failure_time
 * @property int $priority
 * @property string $object_phid
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerActiveTask extends PhabricatorWorkerTask
{

    /**
     * @var
     */
    public $_taskData;
    /**
     * @var
     */
    public $_serverTime;
    /**
     * @var
     */
    public $serverTime;
    /**
     * @var
     */
    public $localTime;

    /**
     * @var
     */
    public $executionException;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_activetask';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['failure_time', 'integer'],
        ]);
    }


    /**
     * @param $server_time
     * @return $this
     * @author 陈妙威
     */
    public function setServerTime($server_time)
    {
        $this->serverTime = $server_time;
        $this->localTime = time();
        return $this;
    }


    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $this->checkLease();
        return $this->forceSaveWithoutLease();
    }

    /**
     * @param bool $runValidation
     * @param null $attributes
     * @return bool
     * @throws Throwable
     * @author 陈妙威
     */
    public function insert($runValidation = true, $attributes = null)
    {
        $id = self::loadNextCounterValue($this->getDb(), "worker_activetask");
        $this->setID($id);
        return parent::insert($runValidation, $attributes);
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function forceSaveWithoutLease()
    {
        if ($this->getIsNewRecord()) {
            $this->failure_time = 0;
        }

        if ($this->getIsNewRecord()) {
            $data = new PhabricatorWorkerTaskData();
            $data->setData($this->getData());
            $data->save();
            $this->setDataID($data->getID());
        }
        return parent::save();
    }

    /**
     * @throws Exception
     * @author 陈妙威
     */
    protected function checkLease()
    {
        $owner = $this->lease_owner;

        if (!$owner) {
            return;
        }

        if ($owner == PhabricatorWorker::YIELD_OWNER) {
            return;
        }

        $current_server_time = $this->serverTime + (time() - $this->localTime);
        if ($current_server_time >= $this->lease_expires) {
            throw new Exception(
                Yii::t("app",
                    'Trying to update Task {0} ({1}) after lease expiration!',
                    [
                        $this->id,
                        $this->task_class
                    ]));
        }
    }


    /**
     * @return PhabricatorWorkerActiveTask|mixed
     * @throws Exception
     * @throws Throwable
     * @author 陈妙威
     */
    public function executeTask()
    {
        // We do this outside of the try .. catch because we don't have permission
        // to release the lease otherwise.
        $this->checkLease();

        $did_succeed = false;
        $worker = null;
        try {
            $worker = $this->getWorkerInstance();
            $worker->setCurrentWorkerTask($this);

            $maximum_failures = $worker->getMaximumRetryCount();
            if ($maximum_failures !== null) {
                if ($this->failure_count > $maximum_failures) {
                    throw new PhabricatorWorkerPermanentFailureException(
                        Yii::t("app",
                            'Task {0} has exceeded the maximum number of failures ({1}}).',
                            [
                                $this->id,
                                $maximum_failures
                            ]));
                }
            }

            $lease = $worker->getRequiredLeaseTime();
            if ($lease !== null) {
                $this->setLeaseDuration($lease);
            }

            $t_start = microtime(true);
            $worker->executeTask();
            $duration = OranginsUtil::phutil_microseconds_since($t_start);

            $result = $this->archiveTask(
                PhabricatorWorkerArchiveTask::RESULT_SUCCESS,
                $duration);
            $did_succeed = true;
        } catch (PhabricatorWorkerPermanentFailureException $ex) {
            Yii::error($ex);
            $result = $this->archiveTask(
                PhabricatorWorkerArchiveTask::RESULT_FAILURE,
                0);
            $result->setExecutionException($ex);
        } catch (PhabricatorWorkerYieldException $ex) {
            Yii::error($ex);

            $this->setExecutionException($ex);
            $this->lease_owner = PhabricatorWorker::YIELD_OWNER;

            $retry = $ex->getDuration();
            $retry = max($retry, 5);

            // NOTE: As a side effect, this saves the object.
            $this->setLeaseDuration($retry);

            $result = $this;
        } catch (Exception $ex) {
            Yii::error($ex);

            $this->setExecutionException($ex);
            $this->failure_count += 1;
            $this->failure_time = time();
            $this->save();

            $retry = null;
            if ($worker) {
                $retry = $worker->getWaitBeforeRetry($this);
            }

            $retry = OranginsUtil::coalesce($retry, PhabricatorWorkerLeaseQuery::getDefaultWaitBeforeRetry());

            // NOTE: As a side effect, this saves the object.
            $this->setLeaseDuration($retry);

            $result = $this;
        }

        // NOTE: If this throws, we don't want it to cause the task to fail again,
        // so execute it out here and just let the exception escape.
        if ($did_succeed) {
            // Default the new task priority to our own priority.
            $defaults = array(
                'priority' => (int)$this->priority,
            );
            $worker->flushTaskQueue($defaults);
        }

        return $result;
    }


    /**
     * @param $lease_duration
     * @return bool
     * @throws Exception
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function setLeaseDuration($lease_duration)
    {
        $this->checkLease();
        $server_lease_expires = $this->serverTime + $lease_duration;
        $this->setLeaseExpires($server_lease_expires);

        // NOTE: This is primarily to allow unit tests to set negative lease
        // durations so they don't have to wait around for leases to expire. We
        // check that the lease is valid above.
        return $this->forceSaveWithoutLease();
    }

    /**
     * @param $result
     * @param $duration
     * @return mixed
     * @throws Exception
     * @throws Throwable
     * @author 陈妙威
     */
    public function archiveTask($result, $duration)
    {
        if ($this->getID() === null) {
            throw new Exception(
                Yii::t("app", "Attempting to archive a task which hasn't been saved!"));
        }

        $this->checkLease();

        /** @var PhabricatorWorkerArchiveTask $archive */
        $archive = (new PhabricatorWorkerArchiveTask())
            ->setID($this->getID())
            ->setTaskClass($this->getTaskClass())
            ->setLeaseOwner($this->getLeaseOwner())
            ->setLeaseExpires($this->getLeaseExpires())
            ->setFailureCount($this->getFailureCount())
            ->setDataID($this->getDataID())
            ->setPriority($this->getPriority())
            ->setObjectPHID($this->getObjectPHID());


        $archive
            ->setResult($result)
            ->setDuration($duration);

        // NOTE: This deletes the active task (this object)!
        $archive->save();
        return $archive;
    }

    /**
     * @return PhabricatorQuery|PhabricatorWorkerActiveTaskQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorWorkerActiveTaskQuery(get_called_class());
    }
}
