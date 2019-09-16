<?php

namespace orangins\modules\conpherence\models;

use Yii;

/**
 * This is the model class for table "conpherence_participant".
 *
 * @property int $id
 * @property string $participant_phid
 * @property string $conpherence_phid
 * @property int $seen_message_count
 * @property string $settings
 * @property int $created_at
 * @property int $updated_at
 */
class ConpherenceParticipant extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_participant';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['participant_phid', 'conpherence_phid', 'seen_message_count', 'settings'], 'required'],
            [['seen_message_count', 'created_at', 'updated_at'], 'integer'],
            [['settings'], 'string'],
            [['participant_phid', 'conpherence_phid'], 'string', 'max' => 64],
            [['conpherence_phid', 'participant_phid'], 'unique', 'targetAttribute' => ['conpherence_phid', 'participant_phid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'participant_phid' => Yii::t('app', 'Participant Phid'),
            'conpherence_phid' => Yii::t('app', 'Conpherence Phid'),
            'seen_message_count' => Yii::t('app', 'Seen Message Count'),
            'settings' => Yii::t('app', 'Settings'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceParticipantQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceParticipantQuery(get_called_class());
    }

      /**
     * {@inheritdoc}
     * @return ConpherenceParticipantCountQuery
     */
    public static function countFind()
    {
        return new ConpherenceParticipantCountQuery(get_called_class());
    }
}
