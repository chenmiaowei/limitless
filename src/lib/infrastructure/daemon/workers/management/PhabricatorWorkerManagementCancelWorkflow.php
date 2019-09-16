<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\time\PhabricatorTime;
use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorWorkerManagementCancelWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
final class PhabricatorWorkerManagementCancelWorkflow
    extends PhabricatorWorkerManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('cancel')
            ->setExamples('**cancel** --id __id__')
            ->setSynopsis(
                \Yii::t("app",
                    'Cancel selected tasks. The work these tasks represent will never ' .
                    'be performed.'))
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
            $can_cancel = !$task->isArchived();
            if (!$can_cancel) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    \Yii::t("app", 'ARCHIVED'),
                    \Yii::t("app",
                        '{0} is already archived, and can not be cancelled.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            // Forcibly break the lease if one exists, so we can archive the
            // task.
            $task->setLeaseOwner(null);
            $task->setLeaseExpires(PhabricatorTime::getNow());
            $task->archiveTask(
                PhabricatorWorkerArchiveTask::RESULT_CANCELLED,
                0);

            $console->writeOut(
                "**<bg:green> %s </bg>** %s\n",
                \Yii::t("app", 'CANCELLED'),
                \Yii::t("app",
                    '{0} was cancelled.',
                    [
                        $this->describeTask($task)
                    ]));
        }

        return 0;
    }

}
