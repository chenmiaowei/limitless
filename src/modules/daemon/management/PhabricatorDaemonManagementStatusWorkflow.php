<?php

namespace orangins\modules\daemon\management;

use orangins\lib\infrastructure\daemon\control\PhabricatorDaemonReference;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use PhutilArgumentParser;
use PhutilConsole;
use PhutilConsoleTable;

/**
 * Class PhabricatorDaemonManagementStatusWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementStatusWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('status')
            ->setSynopsis(\Yii::t("app",'Show status of running daemons.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'local',
                        'help' => \Yii::t("app",'Show only local daemons.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \FilesystemException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        if ($args->getArg('local')) {
            $daemons = $this->loadRunningDaemons();
        } else {
            $daemons = $this->loadAllRunningDaemons();
        }

        if (!$daemons) {
            $console->writeErr(
                "%s\n",
                \Yii::t("app",'There are no running Phabricator daemons.'));
            return 1;
        }

        $status = 0;

        $table = (new PhutilConsoleTable())
            ->addColumns(array(
                'id' => array(
                    'title' => \Yii::t("app",'Log'),
                ),
                'daemonID' => array(
                    'title' => \Yii::t("app",'Daemon'),
                ),
                'host' => array(
                    'title' => \Yii::t("app",'Host'),
                ),
                'pid' => array(
                    'title' => \Yii::t("app",'Overseer'),
                ),
                'started' => array(
                    'title' => \Yii::t("app",'Started'),
                ),
                'daemon' => array(
                    'title' => \Yii::t("app",'Class'),
                ),
                'argv' => array(
                    'title' => \Yii::t("app",'Arguments'),
                ),
            ));

        foreach ($daemons as $daemon) {
            if ($daemon instanceof PhabricatorDaemonLog) {
                $table->addRow(array(
                    'id' => $daemon->getID(),
                    'daemonID' => $daemon->getDaemonID(),
                    'host' => $daemon->getHost(),
                    'pid' => $daemon->getPID(),
                    'started' => date('M j Y, g:i:s A', $daemon->created_at),
                    'daemon' => $daemon->getDaemon(),
                    'argv' => csprintf('%LR', $daemon->getExplicitArgv()),
                ));
            } else if ($daemon instanceof PhabricatorDaemonReference) {
                $name = $daemon->getName();
                if (!$daemon->isRunning()) {
                    $daemon->updateStatus(PhabricatorDaemonLog::STATUS_DEAD);
                    $status = 2;
                    $name = \Yii::t("app",'<DEAD> %s', $name);
                }

                $daemon_log = $daemon->getDaemonLog();
                $id = null;
                $daemon_id = null;
                if ($daemon_log) {
                    $id = $daemon_log->getID();
                    $daemon_id = $daemon_log->getDaemonID();
                }

                $table->addRow(array(
                    'id' => $id,
                    'daemonID' => $daemon_id,
                    'host' => 'localhost',
                    'pid' => $daemon->getPID(),
                    'started' => $daemon->getEpochStarted()
                        ? date('M j Y, g:i:s A', $daemon->getEpochStarted())
                        : null,
                    'daemon' => $name,
                    'argv' => csprintf('%LR', $daemon->getArgv()),
                ));
            }
        }

        $table->draw();
    }

}
