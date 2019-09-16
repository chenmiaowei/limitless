<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorWorkerManagementRetryWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
final class PhabricatorWorkerManagementRetryWorkflow
    extends PhabricatorWorkerManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('retry')
            ->setExamples('**retry** --id __id__')
            ->setSynopsis(
                \Yii::t("app",
                    'Retry selected tasks which previously failed permanently or ' .
                    'were cancelled. Only archived, unsuccessful tasks can be ' .
                    'retried.'))
            ->setArguments($this->getTaskSelectionArguments());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilArgumentUsageException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $tasks = $this->loadTasks($args);

        foreach ($tasks as $task) {
            if (!$task->isArchived()) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    \Yii::t("app", 'ACTIVE'),
                    \Yii::t("app",
                        '{0} is already in the active task queue.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            $result_success = PhabricatorWorkerArchiveTask::RESULT_SUCCESS;
            if ($task->getResult() == $result_success) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    \Yii::t("app", 'SUCCEEDED'),
                    \Yii::t("app",
                        '{0} has already succeeded, and can not be retried.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            $task->unarchiveTask();

            $console->writeOut(
                "**<bg:green> %s </bg>** %s\n",
                \Yii::t("app", 'QUEUED'),
                \Yii::t("app",
                    '{0} was queued for retry.',
                    [
                        $this->describeTask($task)
                    ]));
        }

        return 0;
    }

}
