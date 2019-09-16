<?php

namespace orangins\modules\daemon\models;

use orangins\lib\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "daemon_logevent".
 *
 * @property int $id
 * @property int $log_id
 * @property string $log_type
 * @property string $message
 * @property int $epoch
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorDaemonLogEvent extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daemon_logevent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['log_id', 'epoch'], 'integer'],
            [['message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['log_type'], 'string', 'max' => 4],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'log_id' => Yii::t('app', 'Log ID'),
            'log_type' => Yii::t('app', 'Log Type'),
            'message' => Yii::t('app', 'Message'),
            'epoch' => Yii::t('app', 'Epoch'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
