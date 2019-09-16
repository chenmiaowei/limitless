<?php

namespace orangins\lib\infrastructure\daemon\workers\action;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use PhutilTypeSpec;

/**
 * Trigger action which queues a task.
 *
 * Most triggers should take this action: triggers need to execute as quickly
 * as possible, and should generally queue tasks instead of doing any real
 * work.
 *
 * In some cases, triggers could execute more quickly by examining the
 * scheduled action time and comparing it to the current time, then exiting
 * early if the trigger is executing too far away from real time (for example,
 * it might not make sense to send a meeting reminder after a meeting already
 * happened).
 *
 * However, in most cases the task needs to have this logic anyway (because
 * there may be another arbitrarily long delay between when this code executes
 * and when the task executes), and the cost of queueing a task is very small,
 * and situations where triggers are executing far away from real time should
 * be rare (major downtime or serious problems with the pipeline).
 *
 * The properties of this action map to the parameters of
 * @{method:PhabricatorWorker::scheduleTask}.
 */
final class PhabricatorScheduleTaskTriggerAction
    extends PhabricatorTriggerAction
{

    /**
     * @param array $properties
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @author 陈妙威
     */
    public function validateProperties(array $properties)
    {
        PhutilTypeSpec::checkMap(
            $properties,
            array(
                'class' => 'string',
                'data' => 'map<string, wild>',
                'options' => 'map<string, wild>',
            ));
    }

    /**
     * @param $last_epoch
     * @param $this_epoch
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute($last_epoch, $this_epoch)
    {
        PhabricatorWorker::scheduleTask(
            $this->getProperty('class'),
            $this->getProperty('data') + array(
                'trigger.last-epoch' => $last_epoch,
                'trigger.this-epoch' => $this_epoch,
            ),
            $this->getProperty('options'));
    }

}
