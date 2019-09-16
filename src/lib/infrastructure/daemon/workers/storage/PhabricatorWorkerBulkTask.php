<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;


use Yii;

/**
 * This is the model class for table "worker_bulktask".
 *
 * @property int $id
 * @property string $bulk_job_phid
 * @property string $object_phid
 * @property string $status
 * @property string $data
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerBulkTask
    extends PhabricatorWorkerDAO
{

    /**
     *
     */
    const STATUS_WAITING = 'waiting';
    /**
     *
     */
    const STATUS_RUNNING = 'running';
    /**
     *
     */
    const STATUS_DONE = 'done';
    /**
     *
     */
    const STATUS_FAIL = 'fail';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_bulktask';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['bulk_job_phid', 'object_phid'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'bulk_job_phid' => Yii::t('app', 'Bulk Job PHID'),
            'object_phid' => Yii::t('app', 'Object PHID'),
            'status' => Yii::t('app', 'State'),
            'data' => Yii::t('app', 'Data'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @param $object_phid
     * @return mixed
     * @author 陈妙威
     */
    public static function initializeNewTask(
        PhabricatorWorkerBulkJob $job,
        $object_phid)
    {

        return (new PhabricatorWorkerBulkTask())
            ->setBulkJobPHID($job->getPHID())
            ->setStatus(self::STATUS_WAITING)
            ->setObjectPHID($object_phid);
    }

    /**
     * @return string
     */
    public function getBulkJobPHID()
    {
        return $this->bulk_job_phid;
    }

    /**
     * @param string $bulk_job_phid
     * @return self
     */
    public function setBulkJobPHID($bulk_job_phid)
    {
        $this->bulk_job_phid = $bulk_job_phid;
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
     * @return self
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }


    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
