<?php

namespace orangins\modules\daemon\models;

use orangins\lib\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "daemon_locklog".
 *
 * @property int $id
 * @property string $lock_name
 * @property int $lock_released
 * @property string $lock_parameters
 * @property string $lock_context
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorDaemonLockLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daemon_locklog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lock_name', 'lock_parameters', 'lock_context'], 'required'],
            [['lock_released'], 'integer'],
            [['lock_parameters', 'lock_context'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['lock_name'], 'string', 'max' => 64],
            [['lock_name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'lock_name' => Yii::t('app', 'Lock Name'),
            'lock_released' => Yii::t('app', 'Lock Released'),
            'lock_parameters' => Yii::t('app', 'Lock Parameters'),
            'lock_context' => Yii::t('app', 'Lock Context'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getLockName()
    {
        return $this->lock_name;
    }

    /**
     * @param string $lock_name
     * @return self
     */
    public function setLockName($lock_name)
    {
        $this->lock_name = $lock_name;
        return $this;
    }

    /**
     * @return int
     */
    public function getLockReleased()
    {
        return $this->lock_released;
    }

    /**
     * @param int $lock_released
     * @return self
     */
    public function setLockReleased($lock_released)
    {
        $this->lock_released = $lock_released;
        return $this;
    }

    /**
     * @return string
     */
    public function getLockParameters()
    {
        return $this->lock_parameters;
    }

    /**
     * @param string $lock_parameters
     * @return self
     */
    public function setLockParameters($lock_parameters)
    {
        $this->lock_parameters = $lock_parameters;
        return $this;
    }

    /**
     * @return string
     */
    public function getLockContext()
    {
        return $this->lock_context;
    }

    /**
     * @param string $lock_context
     * @return self
     */
    public function setLockContext($lock_context)
    {
        $this->lock_context = $lock_context;
        return $this;
    }

}
