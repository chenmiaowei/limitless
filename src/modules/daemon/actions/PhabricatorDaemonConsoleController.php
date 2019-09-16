<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerLeaseQuery;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerTriggerQuery;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTrigger;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\query\PhabricatorDaemonLogQuery;
use orangins\modules\daemon\view\PhabricatorDaemonLogListView;
use orangins\modules\daemon\view\PhabricatorDaemonTasksTableView;
use PhutilDaemonHandle;
use PhutilNumber;

/**
 * Class PhabricatorDaemonConsoleController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonConsoleController
    extends PhabricatorDaemonController
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $window_start = (time() - (60 * 15));

        // Assume daemons spend about 250ms second in overhead per task acquiring
        // leases and doing other bookkeeping. This is probably an over-estimation,
        // but we'd rather show that utilization is too high than too low.
        $lease_overhead = 0.250;

        /** @var PhabricatorWorkerArchiveTask[] $completed */
        $completed = PhabricatorWorkerArchiveTask::find()
            ->withDateModifiedSince($window_start)
            ->execute();

        $failed = PhabricatorWorkerActiveTask::find()->andWhere("failure_time > :failure_time", [
            ":failure_time" => $window_start
        ])->all();

        $usage_total = 0;
        $usage_start = PHP_INT_MAX;

        $completed_info = array();
        foreach ($completed as $completed_task) {
            $class = $completed_task->getTaskClass();
            if (empty($completed_info[$class])) {
                $completed_info[$class] = array(
                    'n' => 0,
                    'duration' => 0,
                );
            }
            $completed_info[$class]['n']++;
            $duration = $completed_task->getDuration();
            $completed_info[$class]['duration'] += $duration;

            // NOTE: Duration is in microseconds, but we're just using seconds to
            // compute utilization.
            $usage_total += $lease_overhead + ($duration / 1000000);
            $usage_start = min($usage_start, $completed_task->updated_at);
        }

        $completed_info = isort($completed_info, 'n');

        $rows = array();
        foreach ($completed_info as $class => $info) {
            $rows[] = array(
                $class,
                number_format($info['n']),
                \Yii::t("app",'%s us', new PhutilNumber((int)($info['duration'] / $info['n']))),
            );
        }

        if ($failed) {
            // Add the time it takes to restart the daemons. This includes a guess
            // about other overhead of 2X.
            $restart_delay = PhutilDaemonHandle::getWaitBeforeRestart();
            $usage_total += $restart_delay * count($failed) * 2;
            foreach ($failed as $failed_task) {
                $usage_start = min($usage_start, $failed_task->getFailureTime());
            }

            $rows[] = array(
                phutil_tag('em', array(), \Yii::t("app",'Temporary Failures')),
                count($failed),
                null,
            );
        }

        /** @var PhabricatorDaemonLog[] $logs */
        $logs = PhabricatorDaemonLog::find()
            ->setViewer($viewer)
            ->withStatus(PhabricatorDaemonLogQuery::STATUS_ALIVE)
            ->setAllowStatusWrites(true)
            ->execute();

        $taskmasters = 0;
        foreach ($logs as $log) {
            if ($log->getDaemon() == 'PhabricatorTaskmasterDaemon') {
                $taskmasters++;
            }
        }

        if ($taskmasters && $usage_total) {
            // Total number of wall-time seconds the daemons have been running since
            // the oldest event. For very short times round up to 15s so we don't
            // render any ridiculous numbers if you reload the page immediately after
            // restarting the daemons.
            $available_time = $taskmasters * max(15, (time() - $usage_start));

            // Percentage of those wall-time seconds we can account for, which the
            // daemons spent doing work:
            $used_time = ($usage_total / $available_time);

            $rows[] = array(
                phutil_tag('em', array(), \Yii::t("app",'Queue Utilization (Approximate)')),
                sprintf('%.1f%%', 100 * $used_time),
                null,
            );
        }

        $completed_table = new AphrontTableView($rows);
        $completed_table->setNoDataString(
            \Yii::t("app",'No tasks have completed in the last 15 minutes.'));
        $completed_table->setHeaders(
            array(
                \Yii::t("app",'Class'),
                \Yii::t("app",'Count'),
                \Yii::t("app",'Avg'),
            ));
        $completed_table->setColumnClasses(
            array(
                'wide',
                'n',
                'n',
            ));

        $completed_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Recently Completed Tasks (Last 15m)'))
            ->setTable($completed_table);

        $daemon_table = (new PhabricatorDaemonLogListView())
            ->setUser($viewer)
            ->setDaemonLogs($logs);

        $daemon_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Active Daemons'))
            ->setTable($daemon_table);

        $tasks = (new PhabricatorWorkerLeaseQuery())
            ->setSkipLease(true)
            ->withLeasedTasks(true)
            ->setLimit(100)
            ->execute();

        $tasks_table = (new PhabricatorDaemonTasksTableView())
            ->setTasks($tasks)
            ->setNoDataString(\Yii::t("app",'No tasks are leased by workers.'));

        $leased_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Leased Tasks'))
            ->setTable($tasks_table);

        $task_table = new PhabricatorWorkerActiveTask();
        $queued = $task_table->getDb()
            ->createCommand("SELECT task_class, count(*) N FROM ".$task_table::tableName()." GROUP BY task_class ORDER BY N DESC")->queryAll();

        $rows = array();
        foreach ($queued as $row) {
            $rows[] = array(
                $row['task_class'],
                number_format($row['N']),
            );
        }

        $queued_table = new AphrontTableView($rows);
        $queued_table->setHeaders(
            array(
                \Yii::t("app",'Class'),
                \Yii::t("app",'Count'),
            ));
        $queued_table->setColumnClasses(
            array(
                'wide',
                'n',
            ));
        $queued_table->setNoDataString(\Yii::t("app",'Task queue is empty.'));

        $queued_panel = new PHUIObjectBoxView();
        $queued_panel->setHeaderText(\Yii::t("app",'Queued Tasks'));
        $queued_panel->setTable($queued_table);

        $upcoming = (new PhabricatorWorkerLeaseQuery())
            ->setLimit(10)
            ->setSkipLease(true)
            ->execute();

        $upcoming_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Next In Queue'))
            ->setTable(
                (new PhabricatorDaemonTasksTableView())
                    ->setTasks($upcoming)
                    ->setNoDataString(\Yii::t("app",'Task queue is empty.')));

        $triggers = PhabricatorWorkerTrigger::find()
            ->setViewer($viewer)
            ->setOrder(PhabricatorWorkerTriggerQuery::ORDER_EXECUTION)
            ->withNextEventBetween(0, null)
            ->needEvents(true)
            ->setLimit(10)
            ->execute();

        $triggers_table = $this->buildTriggersTable($triggers);

        $triggers_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Upcoming Triggers'))
            ->setTable($triggers_table);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app",'Console'));

        $nav = $this->buildSideNavView();
        $nav->selectFilter('/');

        return $this->newPage()
            ->setTitle(\Yii::t("app",'Console'))
            ->setNavigation($nav)
            ->appendChild(array(
                $crumbs,
                $completed_panel,
                $daemon_panel,
                $queued_panel,
                $leased_panel,
                $upcoming_panel,
                $triggers_panel,
            ));

    }

    /**
     * @param PhabricatorWorkerTrigger[] $triggers
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \ReflectionException
     */
    private function buildTriggersTable(array $triggers)
    {
        $viewer = $this->getViewer();

        $rows = array();
        foreach ($triggers as $trigger) {
            $event = $trigger->getEvent();
            if ($event) {
                $last_epoch = $event->getLastEventEpoch();
                $next_epoch = $event->getNextEventEpoch();
            } else {
                $last_epoch = null;
                $next_epoch = null;
            }

            $rows[] = array(
                $trigger->getID(),
                $trigger->getClockClass(),
                $trigger->getActionClass(),
                $last_epoch ? OranginsViewUtil::phabricator_datetime($last_epoch, $viewer) : null,
                $next_epoch ? OranginsViewUtil::phabricator_datetime($next_epoch, $viewer) : null,
            );
        }

        return (new AphrontTableView($rows))
            ->setNoDataString(\Yii::t("app",'There are no upcoming event triggers.'))
            ->setHeaders(
                array(
                    \Yii::t("app",'ID'),
                    \Yii::t("app",'Clock'),
                    \Yii::t("app",'Action'),
                    \Yii::t("app",'Last'),
                    \Yii::t("app",'Next'),
                ))
            ->setColumnClasses(
                array(
                    '',
                    '',
                    'wide',
                    'date',
                    'date',
                ));
    }

}
