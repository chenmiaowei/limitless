<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTaskData;
use orangins\lib\time\PhabricatorTime;
use PhutilArgumentParser;
use PhutilConsole;
use Yii;

/**
 * Class PhabricatorWorkerManagementExecuteWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
final class PhabricatorWorkerManagementExecuteWorkflow
    extends PhabricatorWorkerManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('execute')
            ->setExamples('**execute** --id __id__')
            ->setSynopsis(
                Yii::t("app",
                    'Execute a task explicitly. This command ignores leases, is ' .
                    'dangerous, and may cause work to be performed twice.'))
            ->setArguments($this->getTaskSelectionArguments());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilArgumentUsageException
     * @throws \Throwable
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        /** @var PhabricatorWorkerActiveTask[] $tasks */
        $tasks = $this->loadTasks($args);

        foreach ($tasks as $task) {
            $can_execute = !$task->isArchived();
            if (!$can_execute) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    Yii::t("app", 'ARCHIVED'),
                    Yii::t("app",
                        '{0} is already archived, and can not be executed.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            // NOTE: This ignores leases, maybe it should respect them without
            // a parameter like --force?

            $task->setLeaseOwner(null);
            $task->setLeaseExpires(PhabricatorTime::getNow());
            $task->save();

            $task_data = (new PhabricatorWorkerTaskData())->loadOneWhere(
                'id = %d',
                $task->getDataID());
            $task->setData($task_data->getData());

            echo tsprintf(
                "%s\n",
                Yii::t("app",
                    'Executing task {0} ({1})...',
                    [
                        $task->getID(),
                        $task->getTaskClass()
                    ]));

            $task = $task->executeTask();
            $ex = $task->getExecutionException();

            if ($ex) {
                throw $ex;
            }
        }

        return 0;
    }

}
