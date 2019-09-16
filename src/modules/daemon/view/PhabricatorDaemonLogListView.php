<?php

namespace orangins\modules\daemon\view;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\daemon\models\PhabricatorDaemonLog;

/**
 * Class PhabricatorDaemonLogListView
 * @package orangins\modules\daemon\view
 * @author 陈妙威
 */
final class PhabricatorDaemonLogListView extends AphrontView
{

    /**
     * @var
     */
    private $daemonLogs;

    /**
     * @param array $daemon_logs
     * @return $this
     * @author 陈妙威
     */
    public function setDaemonLogs(array $daemon_logs)
    {
        assert_instances_of($daemon_logs, 'PhabricatorDaemonLog');
        $this->daemonLogs = $daemon_logs;
        return $this;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();

        $rows = array();
        $daemons = $this->daemonLogs;

        foreach ($daemons as $daemon) {
            $id = $daemon->getID();
            $host = $daemon->getHost();
            $pid = $daemon->getPID();
            $name = phutil_tag(
                'a',
                array(
                    'href' => "/daemon/log/{$id}/",
                ),
                $daemon->getDaemon());

            $status = $daemon->getStatus();
            switch ($status) {
                case PhabricatorDaemonLog::STATUS_RUNNING:
                    $status_icon = 'fa-rocket green';
                    $status_label = \Yii::t("app",'Running');
                    $status_tip = \Yii::t("app",'This daemon is running.');
                    break;
                case PhabricatorDaemonLog::STATUS_DEAD:
                    $status_icon = 'fa-warning red';
                    $status_label = \Yii::t("app",'Dead');
                    $status_tip = \Yii::t("app",
                        'This daemon has been lost or exited uncleanly, and is ' .
                        'presumed dead.');
                    break;
                case PhabricatorDaemonLog::STATUS_EXITING:
                    $status_icon = 'fa-check';
                    $status_label = \Yii::t("app",'Shutting Down');
                    $status_tip = \Yii::t("app",'This daemon is shutting down.');
                    break;
                case PhabricatorDaemonLog::STATUS_EXITED:
                    $status_icon = 'fa-check grey';
                    $status_label = \Yii::t("app",'Exited');
                    $status_tip = \Yii::t("app",'This daemon exited cleanly.');
                    break;
                case PhabricatorDaemonLog::STATUS_WAIT:
                    $status_icon = 'fa-clock-o blue';
                    $status_label = \Yii::t("app",'Waiting');
                    $status_tip = \Yii::t("app",
                        'This daemon encountered an error recently and is waiting a ' .
                        'moment to restart.');
                    break;
                case PhabricatorDaemonLog::STATUS_UNKNOWN:
                default:
                    $status_icon = 'fa-warning orange';
                    $status_label = \Yii::t("app",'Unknown');
                    $status_tip = \Yii::t("app",
                        'This daemon has not reported its status recently. It may ' .
                        'have exited uncleanly.');
                    break;
            }

            $status = phutil_tag(
                'span',
                array(
                    'sigil' => 'has-tooltip',
                    'meta' => array(
                        'tip' => $status_tip,
                    ),
                ),
                array(
                    id(new PHUIIconView())->setIcon($status_icon),
                    ' ',
                    $status_label,
                ));

            $launched = OranginsViewUtil::phabricator_datetime($daemon->created_at, $viewer);

            $rows[] = array(
                $id,
                $host,
                $pid,
                $name,
                $status,
                $launched,
            );
        }

        $table = id(new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app",'ID'),
                    \Yii::t("app",'Host'),
                    \Yii::t("app",'PPID'),
                    \Yii::t("app",'Daemon'),
                    \Yii::t("app",'Status'),
                    \Yii::t("app",'Launched'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    null,
                    'pri',
                    'wide',
                    'right date',
                ));

        return $table;
    }

}
