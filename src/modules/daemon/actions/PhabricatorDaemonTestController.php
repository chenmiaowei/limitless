<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\infrastructure\daemon\workers\action\PhabricatorScheduleTaskTriggerAction;
use orangins\lib\infrastructure\daemon\workers\clock\PhabricatorOneTimeTriggerClock;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;

/**
 * Class PhabricatorDaemonConsoleController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonTestController
    extends PhabricatorDaemonController
{

    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $clock = new PhabricatorOneTimeTriggerClock();
        $clock->setProperties(array(
            'epoch' => phutil_units('5 second in seconds') + time(),
        ));

        $trigger_action = new PhabricatorScheduleTaskTriggerAction();
        $trigger_action->setProperties(array(
            'class' => 'PhabricatorDaemonTestWorker',
            'data' => array(
                "ids" => [1111]
            ),
            'options' => array(
                'priority' => PhabricatorWorker::PRIORITY_DEFAULT,
            ),
        ));

        $phabricatorWorkerTrigger = new PhabricatorWorkerTrigger();
        $trigger = $phabricatorWorkerTrigger
            ->setClock($clock)
            ->setAction($trigger_action)
            ->save();

        return $this->newDialog()
            ->setTitle("Test")
            ->appendChild("生成成功");
    }
}
