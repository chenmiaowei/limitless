<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use PhutilMissingSymbolException;
use Yii;
use Exception;

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
abstract class PhabricatorWorkerTask extends PhabricatorWorkerDAO
{

    // NOTE: If you provide additional fields here, make sure they are handled
    // correctly in the archiving process.

    /**
     * @var
     */
    private $data;
    /**
     * @var
     */
    private $executionException;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lease_expires', 'failure_count', 'data_id', 'priority'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['task_class', 'lease_owner', 'object_phid'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'task_class' => Yii::t('app', 'Task Class'),
            'lease_owner' => Yii::t('app', 'Lease Owner'),
            'lease_expires' => Yii::t('app', 'Lease Expires'),
            'failure_count' => Yii::t('app', 'Failure Count'),
            'data_id' => Yii::t('app', 'Data ID'),
            'failure_time' => Yii::t('app', 'Failure Time'),
            'priority' => Yii::t('app', 'Priority'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @param $id
     * @return static
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTaskClass()
    {
        return $this->task_class;
    }

    /**
     * @param string $task_class
     * @return self
     */
    public function setTaskClass($task_class)
    {
        $this->task_class = $task_class;
        return $this;
    }

    /**
     * @return string
     */
    public function getLeaseOwner()
    {
        return $this->lease_owner;
    }

    /**
     * @param string $lease_owner
     * @return self
     */
    public function setLeaseOwner($lease_owner)
    {
        $this->lease_owner = $lease_owner;
        return $this;
    }

    /**
     * @return int
     */
    public function getLeaseExpires()
    {
        return $this->lease_expires;
    }

    /**
     * @param int $lease_expires
     * @return self
     */
    public function setLeaseExpires($lease_expires)
    {
        $this->lease_expires = $lease_expires;
        return $this;
    }

    /**
     * @return int
     */
    public function getFailureCount()
    {
        return $this->failure_count;
    }

    /**
     * @param int $failure_count
     * @return self
     */
    public function setFailureCount($failure_count)
    {
        $this->failure_count = $failure_count;
        return $this;
    }

    /**
     * @return int
     */
    public function getDataID()
    {
        return $this->data_id;
    }

    /**
     * @param int $data_id
     * @return self
     */
    public function setDataID($data_id)
    {
        $this->data_id = $data_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getFailureTime()
    {
        return $this->failure_time;
    }

    /**
     * @param int $failure_time
     * @return self
     */
    public function setFailureTime($failure_time)
    {
        $this->failure_time = $failure_time;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectPHID()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     * @return static
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }


    /**
     * @param Exception $execution_exception
     * @return static
     * @author 陈妙威
     */
    final public function setExecutionException(Exception $execution_exception)
    {
        $this->executionException = $execution_exception;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getExecutionException()
    {
        return $this->executionException;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return json_decode($this->data, true);
    }

    /**
     * @param mixed $data
     * @return self
     * @throws Exception
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    final public function isArchived()
    {
        return ($this instanceof PhabricatorWorkerArchiveTask);
    }

    /**
     * @return PhabricatorWorker
     * @throws PhabricatorWorkerPermanentFailureException
     * @author 陈妙威
     */
    final public function getWorkerInstance()
    {
        $id = $this->getID();
        $class = $this->getTaskClass();

        $allWorkers = PhabricatorWorker::getAllWorkers();
        if (!isset($allWorkers[$class])) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    "Task class '{0}' does not exist!", [
                        $class
                    ]));
        }
        $class = get_class($allWorkers[$class]);

        try {
            // NOTE: If the class does not exist, libphutil will throw an exception.
            class_exists($class);
        } catch (PhutilMissingSymbolException $ex) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    "Task class '{0}' does not exist!", [
                        $class
                    ]));
        }

        if (!is_subclass_of($class, PhabricatorWorker::className())) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    "Task class '{0}' does not extend {1}.", [
                        $class,
                        'PhabricatorWorker'
                    ]));
        }

        $data = $this->getData();
        return newv($class, array($data));
    }
}
