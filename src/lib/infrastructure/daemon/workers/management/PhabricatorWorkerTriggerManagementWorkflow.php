<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;
use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use Yii;

/**
 * Class PhabricatorWorkerTriggerManagementWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
abstract class PhabricatorWorkerTriggerManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTriggerSelectionArguments()
    {
        return array(
            array(
                'name' => 'id',
                'param' => 'id',
                'repeat' => true,
                'help' => Yii::t("app", 'Select one or more triggers by ID.'),
            ),
        );
    }

    /**
     * @param PhutilArgumentParser $args
     * @return PhabricatorWorkerTrigger[]
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function loadTriggers(PhutilArgumentParser $args)
    {
        $ids = $args->getArg('id');
        if (!$ids) {
            throw new PhutilArgumentUsageException(
                Yii::t("app", 'Use {0} to select triggers by ID.', [
                    '--id'
                ]));
        }

        $triggers = PhabricatorWorkerTrigger::find()
            ->setViewer($this->getViewer())
            ->withIDs($ids)
            ->needEvents(true)
            ->execute();
        $triggers = mpull($triggers, null, 'getID');

        foreach ($ids as $id) {
            if (empty($triggers[$id])) {
                throw new PhutilArgumentUsageException(
                    Yii::t("app", 'No trigger exists with id "{0}"!', [
                        $id
                    ]));
            }
        }

        return $triggers;
    }

    /**
     * @param PhabricatorWorkerTrigger $trigger
     * @return string
     * @author 陈妙威
     */
    protected function describeTrigger(PhabricatorWorkerTrigger $trigger)
    {
        return Yii::t("app", 'Trigger %d', $trigger->getID());
    }

}
