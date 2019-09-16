<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use Yii;

/**
 * This is the model class for table "worker_triggerevent".
 *
 * @property int $id
 * @property int $trigger_id
 * @property int $last_event_epoch
 * @property int $next_event_epoch
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerTriggerEvent
    extends PhabricatorWorkerDAO
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_triggerevent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trigger_id', 'last_event_epoch', 'next_event_epoch'], 'integer'],
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
            'trigger_id' => Yii::t('app', 'Trigger ID'),
            'last_event_epoch' => Yii::t('app', 'Last Event Epoch'),
            'next_event_epoch' => Yii::t('app', 'Next Event Epoch'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public static function initializeNewEvent(PhabricatorWorkerTrigger $trigger)
    {
        $event = new PhabricatorWorkerTriggerEvent();
        $event->setTriggerID($trigger->getID());
        return $event;
    }

    /**
     * @return int
     */
    public function getTriggerID()
    {
        return $this->trigger_id;
    }

    /**
     * @param int $trigger_id
     * @return self
     */
    public function setTriggerID($trigger_id)
    {
        $this->trigger_id = $trigger_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastEventEpoch()
    {
        return $this->last_event_epoch;
    }

    /**
     * @param int $last_event_epoch
     * @return self
     */
    public function setLastEventEpoch($last_event_epoch)
    {
        $this->last_event_epoch = $last_event_epoch;
        return $this;
    }

    /**
     * @return int
     */
    public function getNextEventEpoch()
    {
        return $this->next_event_epoch;
    }

    /**
     * @param int $next_event_epoch
     * @return self
     */
    public function setNextEventEpoch($next_event_epoch)
    {
        $this->next_event_epoch = $next_event_epoch;
        return $this;
    }
}
