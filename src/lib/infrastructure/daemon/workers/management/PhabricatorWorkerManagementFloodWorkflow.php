<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorWorkerManagementFloodWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
final class PhabricatorWorkerManagementFloodWorkflow
    extends PhabricatorWorkerManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('flood')
            ->setExamples('**flood**')
            ->setSynopsis(
                \Yii::t("app",
                    'Flood the queue with test tasks. This command is intended for ' .
                    'use when developing and debugging Phabricator.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'duration',
                        'param' => 'seconds',
                        'help' => \Yii::t("app",
                            'Queue tasks which require a specific amount of wall time to ' .
                            'complete. By default, tasks complete as quickly as possible.'),
                        'default' => 0,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $duration = (float)$args->getArg('duration');

        $console->writeOut(
            "%s\n",
            \Yii::t("app", 'Adding many test tasks to worker queue. Use ^C to exit.'));

        $n = 0;
        while (true) {
            PhabricatorWorker::scheduleTask(
                'PhabricatorTestWorker',
                array(
                    'duration' => $duration,
                ));

            if (($n++ % 100) === 0) {
                $console->writeOut('.');
            }
        }
    }

}
