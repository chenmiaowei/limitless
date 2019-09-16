<?php

namespace orangins\modules\file\models;

use Yii;

/**
 * This is the model class for table "file_externalrequest".
 *
 * @property int $id
 * @property string $file_phid
 * @property int $ttl
 * @property string $uri
 * @property string $uri_index
 * @property int $is_successful
 * @property string $response_message
 * @property string $created_at
 * @property string $updated_at
 */
class FileExternalrequest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_externalrequest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ttl', 'uri', 'uri_index', 'is_successful'], 'required'],
            [['ttl', 'is_successful'], 'integer'],
            [['uri', 'response_message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['file_phid'], 'string', 'max' => 64],
            [['uri_index'], 'string', 'max' => 12],
            [['uri_index'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'file_phid' => Yii::t('app', 'File Phid'),
            'ttl' => Yii::t('app', 'Ttl'),
            'uri' => Yii::t('app', 'Uri'),
            'uri_index' => Yii::t('app', 'Uri Index'),
            'is_successful' => Yii::t('app', 'Is Successful'),
            'response_message' => Yii::t('app', 'Response Message'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return FileExternalrequestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileExternalrequestQuery(get_called_class());
    }
}
