<?php

namespace orangins\lib\infrastructure\daemon\workers;

use AphrontQueryException;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\daemon\contentsource\PhabricatorDaemonContentSource;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerYieldException;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;
use orangins\lib\OranginsObject;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;
use PhutilConsole;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use PhutilTypeSpec;
use Exception;
use Throwable;
use Yii;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 * @task config   Configuring Retries and Failures
 */
abstract class PhabricatorWorker extends OranginsObject
{

    /**
     * @var array
     */
    private $data;
    /**
     * @var bool
     */
    private static $runAllTasksInProcess = false;
    /**
     * @var array
     */
    private $queuedTasks = array();
    /**
     * @var
     */
    private $currentWorkerTask;

    // NOTE: Lower priority numbers execute first. The priority numbers have to
    // have the same ordering that IDs do (lowest first) so MySQL can use a
    // multipart key across both of them efficiently.

    /**
     *
     */
    const PRIORITY_ALERTS = 1000;
    /**
     *
     */
    const PRIORITY_DEFAULT = 2000;
    /**
     *
     */
    const PRIORITY_COMMIT = 2500;
    /**
     *
     */
    const PRIORITY_BULK = 3000;
    /**
     *
     */
    const PRIORITY_IMPORT = 4000;

    /**
     *
     */
    const PRIORITY_INDEX   = 3500;

    /**
     * Special owner indicating that the task has yielded.
     */
    const YIELD_OWNER = '(yield)';

    /* -(  Configuring Retries and Failures  )----------------------------------- */


    /**
     * Return the number of seconds this worker needs hold a lease on the task for
     * while it performs work. For most tasks you can leave this at `null`, which
     * will give you a default lease (currently 2 hours).
     *
     * For tasks which may take a very long time to complete, you should return
     * an upper bound on the amount of time the task may require.
     *
     * @return int|null  Number of seconds this task needs to remain leased for,
     *                   or null for a default lease.
     *
     * @task config
     */
    public function getRequiredLeaseTime()
    {
        return null;
    }


    /**
     * Return the maximum number of times this task may be retried before it is
     * considered permanently failed. By default, tasks retry indefinitely. You
     * can throw a @{class:PhabricatorWorkerPermanentFailureException} to cause an
     * immediate permanent failure.
     *
     * @return int|null  Number of times the task will retry before permanent
     *                   failure. Return `null` to retry indefinitely.
     *
     * @task config
     */
    public function getMaximumRetryCount()
    {
        return null;
    }


    /**
     * Return the number of seconds a task should wait after a failure before
     * retrying. For most tasks you can leave this at `null`, which will give you
     * a short default retry period (currently 60 seconds).
     *
     * @param PhabricatorWorkerTask $task
     * @return int|null               Number of seconds to wait between retries,
     *                                or null for a default retry period
     *                                (currently 60 seconds).
     *
     * @task config
     */
    public function getWaitBeforeRetry(PhabricatorWorkerTask $task)
    {
        return null;
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return $this
     * @author 陈妙威
     */
    public function setCurrentWorkerTask(PhabricatorWorkerTask $task)
    {
        $this->currentWorkerTask = $task;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCurrentWorkerTask()
    {
        return $this->currentWorkerTask;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getCurrentWorkerTaskID()
    {
        $task = $this->getCurrentWorkerTask();
        if (!$task) {
            return null;
        }
        return $task->getID();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function doWork();

    /**
     * PhabricatorWorker constructor.
     * @param $data
     */
    final public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final protected function getTaskData()
    {
        return $this->data;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     * @throws PhabricatorWorkerPermanentFailureException
     */
    final protected function getTaskDataValue($key, $default = null)
    {
        $data = $this->getTaskData();
        if (!is_array($data)) {
            throw new PhabricatorWorkerPermanentFailureException(
                Yii::t("app", 'Expected task data to be a dictionary.'));
        }
        return ArrayHelper::getValue($data, $key, $default);
    }

    /**
     * @author 陈妙威
     */
    final public function executeTask()
    {
        $this->doWork();
    }

    /**
     * @param $task_class
     * @param $data
     * @param array $options
     * @return storage\PhabricatorWorkerTask
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @throws Exception
     * @author 陈妙威
     */
    final public static function scheduleTask(
        $task_class,
        $data,
        $options = array())
    {

        PhutilTypeSpec::checkMap(
            $options,
            array(
                'priority' => 'optional int|null',
                'objectPHID' => 'optional string|null',
                'delayUntil' => 'optional int|null',
            ));

        $priority = ArrayHelper::getValue($options, 'priority');
        if ($priority === null) {
            $priority = self::PRIORITY_DEFAULT;
        }
        $object_phid = ArrayHelper::getValue($options, 'objectPHID');

        /** @var PhabricatorWorkerActiveTask $task */
        $task = (new PhabricatorWorkerActiveTask())
            ->setTaskClass($task_class)
            ->setData(phutil_json_encode($data))
            ->setPriority($priority)
            ->setObjectPHID($object_phid);

        $delay = ArrayHelper::getValue($options, 'delayUntil');
        if ($delay) {
            $task->setLeaseExpires($delay);
        }


        if (self::$runAllTasksInProcess) {
            $allWorkers = PhabricatorWorker::getAllWorkers();
            if (!isset($allWorkers[$task_class])) {
                $task->save();
                return $task;
            } else {
                // Do the work in-process.
                /** @var PhabricatorWorker $worker */
                $worker = newv($allWorkers[$task_class], array($data));

                while (true) {
                    try {
                        $worker->executeTask();
                        $worker->flushTaskQueue();
                        break;
                    } catch (PhabricatorWorkerYieldException $ex) {
                        Yii::error(
                            Yii::t("app",
                                'In-process task "{0}" yielded for {1} seconds, sleeping...',
                                [
                                    $task_class,
                                    $ex->getDuration()
                                ]));
                        sleep($ex->getDuration());
                    }
                }

                // Now, save a task row and immediately archive it so we can return an
                // object with a valid ID.
                $task->openTransaction();
                try {
                    $task->save();
                    $archived = $task->archiveTask(
                        PhabricatorWorkerArchiveTask::RESULT_SUCCESS,
                        0);
                    $task->saveTransaction();
                    return $archived;
                } catch (Exception $e) {
                    Yii::error($e);
                    $task->killTransaction();
                    return $task;
                }
            }
        } else {
            $task->save();
            return $task;
        }
    }


    /**
     * @param PhabricatorUser $viewer
     * @return null
     * @author 陈妙威
     */
    public function renderForDisplay(PhabricatorUser $viewer)
    {
        return null;
    }

    /**
     * Set this flag to execute scheduled tasks synchronously, in the same
     * process. This is useful for debugging, and otherwise dramatically worse
     * in every way imaginable.
     * @param $all
     */
    public static function setRunAllTasksInProcess($all)
    {
        self::$runAllTasksInProcess = $all;
    }

    /**
     * @param $pattern
     * @return $this
     * @author 陈妙威
     */
    final protected function log($pattern /* , ... */)
    {
        $console = PhutilConsole::getConsole();
        $argv = func_get_args();
        call_user_func_array(array($console, 'writeLog'), $argv);
        return $this;
    }


    /**
     * Queue a task to be executed after this one succeeds.
     *
     * The followup task will be queued only if this task completes cleanly.
     *
     * @param string    Task class to queue.
     * @param array     Data for the followup task.
     * @param array Options for the followup task.
     * @return PhabricatorWorker
     */
    final protected function queueTask(
        $class,
        array $data,
        array $options = array())
    {
        $this->queuedTasks[] = array($class, $data, $options);
        return $this;
    }


    /**
     * Get tasks queued as followups by @{method:queueTask}.
     *
     * @return array
     */
    final protected function getQueuedTasks()
    {
        return $this->queuedTasks;
    }


    /**
     * Schedule any queued tasks, then empty the task queue.
     *
     * By default, the queue is flushed only if a task succeeds. You can call
     * this method to force the queue to flush before failing (for example, if
     * you are using queues to improve locking behavior).
     *
     * @param array $defaults
     * @return void
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     */
    final public function flushTaskQueue($defaults = array())
    {
        foreach ($this->getQueuedTasks() as $task) {
            list($class, $data, $options) = $task;

            $options = $options + $defaults;

            self::scheduleTask($class, $data, $options);
        }

        $this->queuedTasks = array();
    }


    /**
     * Awaken tasks that have yielded.
     *
     * Reschedules the specified tasks if they are currently queued in a yielded,
     * unleased, unretried state so they'll execute sooner. This can let the
     * queue avoid unnecessary waits.
     *
     * This method does not provide any assurances about when these tasks will
     * execute, or even guarantee that it will have any effect at all.
     *
     * @param array $ids
     * @return void
     */
    final public static function awakenTaskIDs(array $ids)
    {
        if (!$ids) {
            return;
        }

        $table = new PhabricatorWorkerActiveTask();

        // NOTE: At least for now, we're keeping these tasks yielded, just
        // pretending that they threw a shorter yield than they really did.

        // Overlap the windows here to handle minor client/server time differences
        // and because it's likely correct to push these tasks to the head of their
        // respective priorities. There is a good chance they are ready to execute.
        $window = phutil_units('1 hour in seconds');
        $epoch_ago = (PhabricatorTime::getNow() - $window);


        $table::updateAll([
            'lease_expires' => $epoch_ago,
        ], [
            'AND',
            ['IN', 'id', $ids],
            'lease_owner = :lease_owner AND lease_expires > :lease_expires AND failure_count = 0'
        ], [
            ":lease_owner" => self::YIELD_OWNER,
            ":lease_expires" => $epoch_ago,
        ]);
    }

    /**
     * @return PhabricatorContentSource
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function newContentSource()
    {
        return PhabricatorContentSource::newForSource(
            PhabricatorDaemonContentSource::SOURCECONST);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllWorkers()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getClassShortName')
            ->execute();
    }
}
