<?php

namespace orangins\modules\file\models;

use orangins\lib\db\ActiveRecord;
use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use Yii;

/**
 * This is the model class for table "file_storageblobs".
 *
 * @property integer $id
 * @property resource $data
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFileStorageBlob extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file_storageblobs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data'], 'required'],
            [['data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorFileFilePHIDType::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTransactionEditor()
    {
        return null;
    }

    /**
     * @return resource
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param resource $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
