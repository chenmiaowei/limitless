<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\infrastructure\daemon\workers\action\PhabricatorTriggerAction;
use orangins\lib\infrastructure\daemon\workers\clock\PhabricatorTriggerClock;
use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTriggerEvent;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorWorkerTriggerQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerTriggerQuery
    extends PhabricatorPolicyAwareQuery
{

    // NOTE: This is a PolicyAware query so it can work with other infrastructure
    // like handles; triggers themselves are low-level and do not have
    // meaningful policies.

    /**
     *
     */
    const ORDER_ID = 'id';
    /**
     *
     */
    const ORDER_EXECUTION = 'execution';
    /**
     *
     */
    const ORDER_VERSION = 'version';

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $versionMin;
    /**
     * @var
     */
    private $versionMax;
    /**
     * @var
     */
    private $nextEpochMin;
    /**
     * @var
     */
    private $nextEpochMax;

    /**
     * @var
     */
    private $needEvents;
    /**
     * @var string
     */
    private $order = self::ORDER_ID;

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return null;
    }

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withVersionBetween($min, $max)
    {
        $this->versionMin = $min;
        $this->versionMax = $max;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withNextEventBetween($min, $max)
    {
        $this->nextEpochMin = $min;
        $this->nextEpochMax = $max;
        return $this;
    }

    /**
     * @param $need_events
     * @return $this
     * @author 陈妙威
     */
    public function needEvents($need_events)
    {
        $this->needEvents = $need_events;
        return $this;
    }

    /**
     * Set the result order.
     *
     * Note that using `ORDER_EXECUTION` will also filter results to include only
     * triggers which have been scheduled to execute. You should not use this
     * ordering when querying for specific triggers, e.g. by ID or PHID.
     *
     * @param string Result order.
     * @return PhabricatorWorkerTriggerQuery
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @param array $page
     * @return null|void
     * @author 陈妙威
     */
    protected function nextPage(array $page) {
        // NOTE: We don't implement paging because we don't currently ever need
        // it and paging ORDER_EXECUTION is a hassle.

        // (Before T13266, we raised an exception here, but since "nextPage()" is
        // now called even if we don't page we can't do that anymore. Just do
        // nothing instead.)
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws Exception
     */
    protected function loadPage()
    {
        $this->buildJoinClause();
        $this->buildWhereClause();
        $this->buildOrderClause();

        /** @var PhabricatorWorkerTrigger[] $triggers */
        $triggers = $this->all();

        if ($triggers) {
            if ($this->needEvents) {
                $ids = mpull($triggers, 'getID');

                $events = PhabricatorWorkerTriggerEvent::find()
                    ->andWhere(['IN', 'trigger_id', $ids])
                    ->all();
                $events = mpull($events, null, 'getTriggerID');
                foreach ($triggers as $key => $trigger) {
                    $event = ArrayHelper::getValue($events, $trigger->getID());
                    $trigger->attachEvent($event);
                }
            }

            foreach ($triggers as $key => $trigger) {
                $clock_class = $trigger->getClockClass();

                $phabricatorTriggerClocks = PhabricatorTriggerClock::getAllClocks();
                if (!isset($phabricatorTriggerClocks[$clock_class])) {
                    unset($triggers[$key]);
                    continue;
                }

                try {
                    $clock = $phabricatorTriggerClocks[$clock_class];
                    $clock->setProperties($trigger->getClockProperties());
                } catch (Exception $ex) {
                    \Yii::error($ex);
                    unset($triggers[$key]);
                    continue;
                }

                $trigger->attachClock($clock);
            }


            foreach ($triggers as $key => $trigger) {
                $action_class = $trigger->getActionClass();

                $phabricatorTriggerActions = PhabricatorTriggerAction::getAllActions();
                if (!isset($phabricatorTriggerActions[$action_class])) {
                    unset($triggers[$key]);
                    continue;
                }

                try {
                    $action = $phabricatorTriggerActions[$action_class];
                    $action->setProperties($trigger->getActionProperties());
                } catch (Exception $ex) {
                    \Yii::error($ex);
                    unset($triggers[$key]);
                    continue;
                }

                $trigger->attachAction($action);
            }
        }

        return $triggers;
    }

    /**
     * @author 陈妙威
     */
    protected function buildJoinClause()
    {
        if (($this->nextEpochMin !== null) ||
            ($this->nextEpochMax !== null) ||
            ($this->order == self::ORDER_EXECUTION)) {

            $this->innerJoin("worker_triggerevent e", "e.trigger_id = worker_trigger.id");
        }
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'worker_trigger.id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'worker_trigger.phid', $this->phids]);
        }

        if ($this->versionMin !== null) {
            $this->andWhere("worker_trigger.trigger_version>=:trigger_version", [
                ":trigger_version" => $this->versionMin
            ]);
        }

        if ($this->versionMax !== null) {
            $this->andWhere("worker_trigger.trigger_version<=:trigger_version", [
                ":trigger_version" => $this->versionMin
            ]);
        }

        if ($this->nextEpochMin !== null) {
            $this->andWhere("e.next_event_epoch>=:next_event_epoch", [
                ":next_event_epoch" => $this->nextEpochMin
            ]);
        }

        if ($this->nextEpochMax !== null) {
            $this->andWhere("e.next_event_epoch<=:next_event_epoch", [
                ":next_event_epoch" => $this->nextEpochMax
            ]);
        }
    }

    /**
     * @author 陈妙威
     * @throws Exception
     */
    private function buildOrderClause()
    {
        switch ($this->order) {
            case self::ORDER_ID:
                $this->orderBy('worker_trigger.id DESC');
                break;
            case self::ORDER_EXECUTION:
                $this->orderBy('e.next_event_epoch ASC, e.id ASC');
                break;
            case self::ORDER_VERSION:
                $this->orderBy('worker_trigger.trigger_version ASC');
                break;
            default:
                throw new Exception(
                    \Yii::t("app",
                        'Unsupported order "{0}".',
                       [
                           $this->order
                       ]));
        }
    }
}
