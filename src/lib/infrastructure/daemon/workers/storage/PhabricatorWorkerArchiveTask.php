<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerArchiveTaskQuery;
use Exception;

/**
 * This is the model class for table "worker_archivetask".
 *
 * @property int $id
 * @property string $task_class
 * @property string $lease_owner
 * @property int $lease_expires
 * @property int $failure_count
 * @property int $data_id
 * @property int $result
 * @property int $duration
 * @property int $priority
 * @property string $object_phid
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerArchiveTask extends PhabricatorWorkerTask
{

    /**
     *
     */
    const RESULT_SUCCESS = 0;
    /**
     *
     */
    const RESULT_FAILURE = 1;
    /**
     *
     */
    const RESULT_CANCELLED = 2;


    protected $result;

    protected $duration;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_archivetask';
    }


    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return mixed
     * @throws Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getID() === null) {
            throw new Exception(\Yii::t("app",'Trying to archive a task with no ID.'));
        }

        $other = new PhabricatorWorkerActiveTask();
        $this->openTransaction();

        try {
            $other->getDb()
                ->createCommand("DELETE FROM " . $other::tableName() . " WHERE id = :id", [
                    ":id" => $this->getID()
                ])->execute();
            $result = parent::insert($runValidation, $attributeNames);
            $this->saveTransaction();
        } catch (Exception $e) {
            $this->killTransaction();
            $result = false;
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function delete()
    {
        $this->openTransaction();
        if ($this->getDataID()) {
            PhabricatorWorkerTaskData::deleteAll([
                'id' => $this->getDataID()
            ]);
        }

        $result = parent::delete();
        $this->saveTransaction();
        return $result;
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function unarchiveTask()
    {
        $this->openTransaction();
        $active = (new PhabricatorWorkerActiveTask())
            ->setID($this->getID())
            ->setTaskClass($this->getTaskClass())
            ->setLeaseOwner(null)
            ->setLeaseExpires(0)
            ->setFailureCount(0)
            ->setDataID($this->getDataID())
            ->setPriority($this->getPriority())
            ->setObjectPHID($this->getObjectPHID())
            ->insert();

        $this->setDataID(null);
        $this->delete();
        $this->saveTransaction();

        return $active;
    }

    /**
     * @return int
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param int $result
     * @return self
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     * @return self
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }


    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorWorkerArchiveTaskQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorWorkerArchiveTaskQuery(get_called_class());
    }
}
