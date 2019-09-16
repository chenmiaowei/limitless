<?php

namespace orangins\modules\metamta\models;

use Yii;

/**
 * This is the model class for table "metamta_receivedmail".
 *
 * @property int $id
 * @property string $headers
 * @property string $bodies
 * @property string $attachments
 * @property string $related_phid
 * @property string $author_phid
 * @property string $message
 * @property string $message_id_hash
 * @property string $state
 * @property string $created_at
 * @property string $updated_at
 */
class MetamtaReceivedmail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'metamta_receivedmail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['headers', 'bodies', 'attachments', 'message_id_hash', 'state'], 'required'],
            [['headers', 'bodies', 'attachments', 'message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['related_phid', 'author_phid'], 'string', 'max' => 64],
            [['message_id_hash'], 'string', 'max' => 12],
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
            'headers' => Yii::t('app', 'Headers'),
            'bodies' => Yii::t('app', 'Bodies'),
            'attachments' => Yii::t('app', 'Attachments'),
            'related_phid' => Yii::t('app', 'Related Phid'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'message' => Yii::t('app', 'Message'),
            'message_id_hash' => Yii::t('app', 'Message Id Hash'),
            'status' => Yii::t('app', 'State'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
