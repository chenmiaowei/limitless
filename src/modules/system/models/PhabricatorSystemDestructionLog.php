<?php

namespace orangins\modules\system\models;

use Yii;

/**
 * This is the model class for table "system_destructionlog".
 *
 * @property int $id
 * @property string $object_class
 * @property string $root_log_id
 * @property string $object_phid
 * @property double $object_monogram
 * @property int $epoch
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorSystemDestructionLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'system_destructionlog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_class', 'root_log_id', 'object_phid', 'object_monogram', 'epoch'], 'required'],
            [['object_monogram'], 'number'],
            [['epoch', 'created_at', 'updated_at'], 'integer'],
            [['object_class'], 'string', 'max' => 16],
            [['root_log_id'], 'string', 'max' => 255],
            [['object_phid'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_class' => Yii::t('app', 'Object Class'),
            'root_log_id' => Yii::t('app', 'Root Log ID'),
            'object_phid' => Yii::t('app', 'Object PHID'),
            'object_monogram' => Yii::t('app', 'Object Monogram'),
            'epoch' => Yii::t('app', 'Epoch'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getObjectClass()
    {
        return $this->object_class;
    }

    /**
     * @param string $object_class
     * @return self
     */
    public function setObjectClass($object_class)
    {
        $this->object_class = $object_class;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootLogID()
    {
        return $this->root_log_id;
    }

    /**
     * @param string $root_log_id
     * @return self
     */
    public function setRootLogID($root_log_id)
    {
        $this->root_log_id = $root_log_id;
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
     * @return float
     */
    public function getObjectMonogram()
    {
        return $this->object_monogram;
    }

    /**
     * @param float $object_monogram
     * @return self
     */
    public function setObjectMonogram($object_monogram)
    {
        $this->object_monogram = $object_monogram;
        return $this;
    }

    /**
     * @return int
     */
    public function getEpoch()
    {
        return $this->epoch;
    }

    /**
     * @param int $epoch
     * @return self
     */
    public function setEpoch($epoch)
    {
        $this->epoch = $epoch;
        return $this;
    }
}
