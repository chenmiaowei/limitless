<?php

namespace orangins\lib\infrastructure\daemon\workers\management;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;
use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use orangins\lib\time\PhabricatorTime;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use Yii;

/**
 * Class PhabricatorWorkerManagementWorkflow
 * @package orangins\lib\infrastructure\daemon\workers\management
 * @author 陈妙威
 */
abstract class PhabricatorWorkerManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTaskSelectionArguments()
    {
        return array(
            array(
                'name' => 'id',
                'param' => 'id',
                'repeat' => true,
                'help' => Yii::t("app", 'Select one or more tasks by ID.'),
            ),
            array(
                'name' => 'class',
                'param' => 'name',
                'help' => Yii::t("app", 'Select all tasks of a given class.'),
            ),
            array(
                'name' => 'min-failure-count',
                'param' => 'int',
                'help' => Yii::t("app", 'Limit to tasks with at least this many failures.'),
            ),
        );
    }

    /**
     * @param PhutilArgumentParser $args
     * @return PhabricatorWorkerTask[]
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @author 陈妙威
     */
    protected function loadTasks(PhutilArgumentParser $args)
    {
        $ids = $args->getArg('id');
        $class = $args->getArg('class');
        $min_failures = $args->getArg('min-failure-count');

        if (!$ids && !$class && !$min_failures) {
            throw new PhutilArgumentUsageException(
                Yii::t("app", 'Use --id, --class, or --min-failure-count to select tasks.'));
        }

        $active_query = PhabricatorWorkerActiveTask::find();
        $archive_query = PhabricatorWorkerArchiveTask::find();

        if ($ids) {
            $active_query = $active_query->withIDs($ids);
            $archive_query = $archive_query->withIDs($ids);
        }

        if ($class) {
            $class_array = array($class);
            $active_query = $active_query->withClassNames($class_array);
            $archive_query = $archive_query->withClassNames($class_array);
        }

        if ($min_failures) {
            $active_query = $active_query->withFailureCountBetween(
                $min_failures, null);
            $archive_query = $archive_query->withFailureCountBetween(
                $min_failures, null);
        }

        $active_tasks = $active_query->execute();
        $archive_tasks = $archive_query->execute();
        $tasks =
            mpull($active_tasks, null, 'getID') +
            mpull($archive_tasks, null, 'getID');

        if ($ids) {
            foreach ($ids as $id) {
                if (empty($tasks[$id])) {
                    throw new PhutilArgumentUsageException(
                        Yii::t("app", 'No task exists with id "{0}"!', [
                            $id
                        ]));
                }
            }
        }
        if ($class && $min_failures) {
            if (!$tasks) {
                throw new PhutilArgumentUsageException(
                    Yii::t("app", 'No task exists with class "{0}" and at least {1} failures!',
                        [
                            $class,
                            $min_failures
                        ]));
            }
        } else if ($class) {
            if (!$tasks) {
                throw new PhutilArgumentUsageException(
                    Yii::t("app", 'No task exists with class "{0}"!', [
                        $class
                    ]));
            }
        } else if ($min_failures) {
            if (!$tasks) {
                throw new PhutilArgumentUsageException(
                    Yii::t("app", 'No tasks exist with at least {1} failures!', [
                        $min_failures
                    ]));
            }
        }

        // When we lock tasks properly, this gets populated as a side effect. Just
        // fake it when doing manual CLI stuff. This makes sure CLI yields have
        // their expires times set properly.
        foreach ($tasks as $task) {
            if ($task instanceof PhabricatorWorkerActiveTask) {
                $task->setServerTime(PhabricatorTime::getNow());
            }
        }

        return $tasks;
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return string
     * @author 陈妙威
     */
    protected function describeTask(PhabricatorWorkerTask $task)
    {
        return Yii::t("app", 'Task {0} ({1})', [
            $task->getID(), $task->getTaskClass()
        ]);
    }

}
