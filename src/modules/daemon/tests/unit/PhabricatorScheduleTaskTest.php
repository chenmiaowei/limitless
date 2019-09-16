<?php

use orangins\lib\infrastructure\daemon\workers\action\PhabricatorScheduleTaskTriggerAction;
use orangins\lib\infrastructure\daemon\workers\clock\PhabricatorMetronomicTriggerClock;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/20
 * Time: 4:00 PM
 * Email: chenmiaowei0914@gmail.com
 */

class PhabricatorScheduleTaskTest  extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
        ];
    }

    /**
     * @throws AphrontQueryException
     * @throws ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function testTrigger()
    {
        PhabricatorWorker::setRunAllTasksInProcess(true);
        $clock = new PhabricatorMetronomicTriggerClock();
        $clock->setProperties(
            array(
                'period' => phutil_units('5 second in seconds'),
            ));

        $trigger_action = new PhabricatorScheduleTaskTriggerAction();
        $trigger_action->setProperties(
            array(
                'class' => 'PhabricatorDaemonTestWorker',
                'data' => array(

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

        $execute = PhabricatorWorkerTrigger::find()
            ->setViewer(\orangins\modules\people\models\PhabricatorUser::getOmnipotentUser())
            ->withPHIDs([$phabricatorWorkerTrigger->getPHID()])
            ->executeOne();
        expect('save success', $trigger)->true();


    }
}