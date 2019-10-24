<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\time\PhabricatorTime;
use PhutilArgumentParser;
use PhutilConsole;
use Yii;

/**
 * Class PhabricatorWorkerManagementFreeWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
final class PhabricatorWorkerManagementFreeWorkflow
    extends PhabricatorWorkerManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('free')
            ->setExamples('**free** --id __id__')
            ->setSynopsis(
                Yii::t("app",
                    'Free leases on selected tasks. If the daemon holding the lease is ' .
                    'still working on the task, this may cause the task to execute ' .
                    'twice.'))
            ->setArguments($this->getTaskSelectionArguments());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilArgumentUsageException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $tasks = $this->loadTasks($args);

        foreach ($tasks as $task) {
            if ($task->isArchived()) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    Yii::t("app", 'ARCHIVED'),
                    Yii::t("app",
                        '{0} is archived; archived tasks do not have leases.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            if ($task->getLeaseOwner() === null) {
                $console->writeOut(
                    "**<bg:yellow> %s </bg>** %s\n",
                    Yii::t("app", 'FREE'),
                    Yii::t("app",
                        '{0} has no active lease.',
                        [
                            $this->describeTask($task)
                        ]));
                continue;
            }

            $task->setLeaseOwner(null);
            $task->setLeaseExpires(PhabricatorTime::getNow());
            $task->save();

            $console->writeOut(
                "**<bg:green> %s </bg>** %s\n",
                Yii::t("app", 'LEASE FREED'),
                Yii::t("app",
                    '{0} was freed from its lease.',
                    [
                        $this->describeTask($task)
                    ]));
        }

        return 0;
    }

}
