<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;


use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\daemon\workers\action\PhabricatorTriggerAction;
use orangins\lib\infrastructure\daemon\workers\clock\PhabricatorTriggerClock;
use orangins\lib\infrastructure\daemon\workers\PhabricatorTriggerDaemon;
use orangins\lib\infrastructure\daemon\workers\phid\PhabricatorWorkerTriggerPHIDType;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerTriggerQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use Yii;

/**
 * This is the model class for table "worker_trigger".
 *
 * @property int $id
 * @property string $phid
 * @property int $trigger_version
 * @property string $clock_class
 * @property string $clock_properties
 * @property string $action_class
 * @property string $action_properties
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerTrigger
    extends ActiveRecordPHID
    implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface
{


    /**
     * @var string
     */
    private $action = self::ATTACHABLE;
    /**
     * @var string
     */
    private $clock = self::ATTACHABLE;
    /**
     * @var string
     */
    private $event = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_trigger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trigger_version'], 'integer'],
            [['action_properties', 'clock_properties'], 'default', 'value' => '[]'],
            [['clock_properties', 'action_properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'clock_class', 'action_class'], 'string', 'max' => 64],
            [['phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'trigger_version' => Yii::t('app', 'Trigger Version'),
            'clock_class' => Yii::t('app', 'Clock Class'),
            'clock_properties' => Yii::t('app', 'Clock Properties'),
            'action_class' => Yii::t('app', 'Action Class'),
            'action_properties' => Yii::t('app', 'Action Properties'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return $this|bool
     * @throws \AphrontQueryException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $this->openTransaction();
        $next_version = PhabricatorWorkerTrigger::loadNextCounterValue((new PhabricatorWorkerTriggerEvent())->getDb(), PhabricatorTriggerDaemon::COUNTER_VERSION);
        $this->setTriggerVersion($next_version);
        $save = parent::save($runValidation, $attributeNames);
        $this->saveTransaction();
        return $save;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function generatePHID()
    {
        return PhabricatorPHID::generateNewPHID(
            PhabricatorWorkerTriggerPHIDType::TYPECONST);
    }

    /**
     * Return the next time this trigger should execute.
     *
     * This method can be called either after the daemon executed the trigger
     * successfully (giving the trigger an opportunity to reschedule itself
     * into the future, if it is a recurring event) or after the trigger itself
     * is changed (usually because of an application edit). The `$is_reschedule`
     * parameter distinguishes between these cases.
     *
     * @param int|null Epoch of the most recent successful event execution.
     * @param bool `true` if we're trying to reschedule the event after
     *   execution; `false` if this is in response to a trigger update.
     * @return int|null Return an epoch to schedule the next event execution,
     *   or `null` to stop the event from executing again.
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function getNextEventEpoch($last_epoch, $is_reschedule)
    {
        return $this->getClock()->getNextEventEpoch($last_epoch, $is_reschedule);
    }


    /**
     * Execute the event.
     *
     * @param int|null Epoch of previous execution, or null if this is the first
     *   execution.
     * @param int Scheduled epoch of this execution. This may not be the same
     *   as the current time.
     * @return void
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function executeTrigger($last_event, $this_event)
    {
        return $this->getAction()->execute($last_event, $this_event);
    }

    /**
     * @return PhabricatorWorkerTriggerEvent
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getEvent()
    {
        return $this->assertAttached($this->event);
    }

    /**
     * @param PhabricatorWorkerTriggerEvent|null $event
     * @return $this
     * @author 陈妙威
     */
    public function attachEvent(PhabricatorWorkerTriggerEvent $event = null)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @param PhabricatorTriggerAction $action
     * @return PhabricatorWorkerTrigger
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function setAction(PhabricatorTriggerAction $action)
    {
        $this->action_class = $action->getClassShortName();
        $this->setActionProperties($action->getProperties());
        return $this->attachAction($action);
    }

    /**
     * @return PhabricatorTriggerAction
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getAction()
    {
        return $this->assertAttached($this->action);
    }

    /**
     * @param PhabricatorTriggerAction $action
     * @return $this
     * @author 陈妙威
     */
    public function attachAction(PhabricatorTriggerAction $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param PhabricatorTriggerClock $clock
     * @return PhabricatorWorkerTrigger
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function setClock(PhabricatorTriggerClock $clock)
    {
        $this->clock_class = $clock->getClassShortName();
        $this->setClockProperties($clock->getProperties());
        return $this->attachClock($clock);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getClock()
    {
        return $this->assertAttached($this->clock);
    }

    /**
     * @param PhabricatorTriggerClock $clock
     * @return $this
     * @author 陈妙威
     */
    public function attachClock(PhabricatorTriggerClock $clock)
    {
        $this->clock = $clock;
        return $this;
    }


    /**
     * Predict the epoch at which this trigger will next fire.
     *
     * @return int|null  Epoch when the event will next fire, or `null` if it is
     *   not planned to trigger.
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function getNextEventPrediction()
    {
        // NOTE: We're basically echoing the database state here, so this won't
        // necessarily be accurate if the caller just updated the object but has
        // not saved it yet. That's a weird use case and would require more
        // gymnastics, so don't bother trying to get it totally correct for now.

        if ($this->getEvent()) {
            return $this->getEvent()->getNextEventEpoch();
        } else {
            return $this->getNextEventEpoch(null, $is_reschedule = false);
        }
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @return mixed|void
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();
        PhabricatorWorkerTriggerEvent::find()
            ->andWhere(['trigger_id' => $this->getID()])
            ->one();
        $this->delete();
        $this->saveTransaction();
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    // NOTE: Triggers are low-level infrastructure and do not have real
    // policies, but implementing the policy interface allows us to use
    // infrastructure like handles.

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::getMostOpenPolicy();
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return true;
    }

    /**
     * @return int
     */
    public function getTriggerVersion()
    {
        return $this->trigger_version;
    }

    /**
     * @param int $trigger_version
     * @return self
     */
    public function setTriggerVersion($trigger_version)
    {
        $this->trigger_version = $trigger_version;
        return $this;
    }

    /**
     * @return string
     */
    public function getClockClass()
    {
        return $this->clock_class;
    }

    /**
     * @param string $clock_class
     * @return self
     */
    public function setClockClass($clock_class)
    {
        $this->clock_class = $clock_class;
        return $this;
    }

    /**
     * @return array
     */
    public function getClockProperties()
    {
        return $this->clock_properties === null ? [] : phutil_json_decode($this->clock_properties);
    }

    /**
     * @param array $clock_properties
     * @return self
     * @throws \Exception
     */
    public function setClockProperties($clock_properties)
    {
        $this->clock_properties = phutil_json_encode($clock_properties);
        return $this;
    }

    /**
     * @return string
     */
    public function getActionClass()
    {
        return $this->action_class;
    }

    /**
     * @param string $action_class
     * @return self
     */
    public function setActionClass($action_class)
    {
        $this->action_class = $action_class;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getActionProperties()
    {
        return $this->action_properties === null ? [] : phutil_json_decode($this->action_properties);
    }

    /**
     * @param string $action_properties
     * @return self
     * @throws \Exception
     */
    public function setActionProperties($action_properties)
    {
        $this->action_properties = phutil_json_encode($action_properties);
        return $this;
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorWorkerTriggerQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorWorkerTriggerQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorWorkerTriggerPHIDType::className();
    }
}
