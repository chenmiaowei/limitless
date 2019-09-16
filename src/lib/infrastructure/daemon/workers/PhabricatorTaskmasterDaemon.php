<?php

namespace orangins\lib\infrastructure\daemon\workers;

use orangins\lib\infrastructure\daemon\PhabricatorDaemon;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerYieldException;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerLeaseQuery;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\modules\cache\PhabricatorCaches;
use PhutilProxyException;

/**
 * Class PhabricatorTaskmasterDaemon
 * @package orangins\lib\infrastructure\daemon\workers
 * @author 陈妙威
 */
final class PhabricatorTaskmasterDaemon extends PhabricatorDaemon
{

    /**
     * @throws PhutilProxyException
     * @throws \AphrontQueryException
     * @throws \Throwable
     * @author 陈妙威
     */
    protected function run()
    {
        do {
            PhabricatorCaches::destroyRequestCache();

            try {
                /** @var PhabricatorWorkerActiveTask[] $tasks */
                $tasks = (new PhabricatorWorkerLeaseQuery())
                    ->setLimit(1)
                    ->execute();
            } catch (\Exception $e) {
                phlog(\Yii::t("app", 'Task failed! {0}', [$e->getMessage()]));
                $tasks = [];
            }

            if ($tasks) {
                $this->willBeginWork();

                foreach ($tasks as $task) {
                    $id = $task->getID();
                    $class = $task->getTaskClass();

                    $this->log(\Yii::t("app", 'Working on task {0} ({1})...', [
                        $id, $class
                    ]));

                    $task = $task->executeTask();

                    $ex = $task->getExecutionException();
                    if ($ex) {
                        if ($ex instanceof PhabricatorWorkerPermanentFailureException) {
                            // NOTE: Make sure these reach the daemon log, even when not
                            // running in "phd.verbose" mode. See T12803 for discussion.
                            $log_exception = new PhutilProxyException(
                                \Yii::t("app",
                                    'Task "{0}" encountered a permanent failure and was ' .
                                    'cancelled.',
                                    [
                                        $id, $class
                                    ]),
                                $ex);
                            phlog($log_exception);
                        } else if ($ex instanceof PhabricatorWorkerYieldException) {
                            $this->log(\Yii::t("app", 'Task {0} yielded.', [
                                $id
                            ]));
                        } else {
                            phlog(print_r($ex, true));
                            phlog(\Yii::t("app", 'Task {0} failed! {1}', [$id, $ex->getMessage()]));
                            throw new PhutilProxyException(
                                \Yii::t("app", 'Error while executing Task ID {0}.', [$id]),
                                $ex);
                        }
                    } else {
                        $this->log(\Yii::t("app", 'Task {0} complete! Moved to archive.', [
                            $id
                        ]));
                    }
                }

                $sleep = 0;
            } else {

                if ($this->getIdleDuration() > 15) {
                    $hibernate_duration = phutil_units('1 minutes in seconds');
                    if ($this->shouldHibernate($hibernate_duration)) {
                        break;
                    }
                }

                // When there's no work, sleep for one second. The pool will
                // autoscale down if we're continuously idle for an extended period
                // of time.
                $this->willBeginIdle();
                $sleep = 1;
            }

            $this->sleep($sleep);
        } while (!$this->shouldExit());
    }

}
