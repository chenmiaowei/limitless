<?php

namespace orangins\modules\file\models;

use Yii;

/**
 * This is the model class for table "file_transformedfile".
 *
 * @property int $id
 * @property string $original_phid
 * @property string $transform
 * @property string $transformed_phid
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorTransformedFile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_transformedfile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['original_phid', 'transform', 'transformed_phid'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['original_phid', 'transformed_phid'], 'string', 'max' => 64],
            [['transform'], 'string', 'max' => 128],
            [['original_phid', 'transform'], 'unique', 'targetAttribute' => ['original_phid', 'transform']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'original_phid' => Yii::t('app', 'Original Phid'),
            'transform' => Yii::t('app', 'Transform'),
            'transformed_phid' => Yii::t('app', 'Transformed Phid'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return FileTransformedfileQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileTransformedfileQuery(get_called_class());
    }

    /**
     * @return string
     */
    public function getOriginalPHID()
    {
        return $this->original_phid;
    }

    /**
     * @param string $original_phid
     * @return self
     */
    public function setOriginalPHID($original_phid)
    {
        $this->original_phid = $original_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransform()
    {
        return $this->transform;
    }

    /**
     * @param string $transform
     * @return self
     */
    public function setTransform($transform)
    {
        $this->transform = $transform;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransformedPHID()
    {
        return $this->transformed_phid;
    }

    /**
     * @param string $transformed_phid
     * @return self
     */
    public function setTransformedPHID($transformed_phid)
    {
        $this->transformed_phid = $transformed_phid;
        return $this;
    }
}
