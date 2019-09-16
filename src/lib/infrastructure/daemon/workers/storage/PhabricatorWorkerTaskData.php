<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use Yii;

/**
 * This is the model class for table "worker_taskdata".
 *
 * @property int $id
 * @property string $data
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerTaskData extends PhabricatorWorkerDAO
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_taskdata';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'data' => Yii::t('app', 'Data'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data ? json_decode($this->data, true) : null;
    }

    /**
     * @param string $data
     * @return self
     * @throws \Exception
     */
    public function setData($data)
    {
        $this->data = phutil_json_encode($data);
        return $this;
    }
}
