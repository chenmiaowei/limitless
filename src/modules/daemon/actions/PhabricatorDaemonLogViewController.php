<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\query\PhabricatorDaemonLogQuery;
use PhutilDaemonHandle;

/**
 * Class PhabricatorDaemonLogViewController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonLogViewController
    extends PhabricatorDaemonController
{

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $log = PhabricatorDaemonLog::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->setAllowStatusWrites(true)
            ->executeOne();
        if (!$log) {
            return new Aphront404Response();
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Daemon %s', $log->getID()));
        $crumbs->setBorder(true);

        $header = (new PHUIHeaderView())
            ->setHeader($log->getDaemon())
            ->setHeaderIcon('fa-pied-piper-alt');

        $tag = (new PHUITagView())
            ->setType(PHUITagView::TYPE_STATE);

        $status = $log->getStatus();
        switch ($status) {
            case PhabricatorDaemonLog::STATUS_UNKNOWN:
                $color = 'orange';
                $name = \Yii::t("app", 'Unknown');
                $icon = 'fa-warning';
                break;
            case PhabricatorDaemonLog::STATUS_RUNNING:
                $color = 'green';
                $name = \Yii::t("app", 'Running');
                $icon = 'fa-rocket';
                break;
            case PhabricatorDaemonLog::STATUS_DEAD:
                $color = 'red';
                $name = \Yii::t("app", 'Dead');
                $icon = 'fa-times';
                break;
            case PhabricatorDaemonLog::STATUS_WAIT:
                $color = 'blue';
                $name = \Yii::t("app", 'Waiting');
                $icon = 'fa-clock-o';
                break;
            case PhabricatorDaemonLog::STATUS_EXITING:
                $color = 'yellow';
                $name = \Yii::t("app", 'Exiting');
                $icon = 'fa-check';
                break;
            case PhabricatorDaemonLog::STATUS_EXITED:
                $color = 'bluegrey';
                $name = \Yii::t("app", 'Exited');
                $icon = 'fa-check';
                break;
        }

        $header->setStatus($icon, $color, $name);

        $properties = $this->buildPropertyListView($log);

        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Daemon Details'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addPropertyList($properties);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter(array(
                $object_box,
            ));

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Daemon Log'))
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

    /**
     * @param PhabricatorDaemonLog $daemon
     * @return PHUIPropertyListView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertyListView(PhabricatorDaemonLog $daemon)
    {
        $request = $this->getRequest();
        $viewer = $request->getUser();

        $view = (new PHUIPropertyListView())
            ->setUser($viewer);

        $id = $daemon->getID();
        $c_epoch = $daemon->created_at;
        $u_epoch = $daemon->updated_at;

        $unknown_time = PhabricatorDaemonLogQuery::getTimeUntilUnknown();
        $dead_time = PhabricatorDaemonLogQuery::getTimeUntilDead();
        $wait_time = PhutilDaemonHandle::getWaitBeforeRestart();

        $details = null;
        $status = $daemon->getStatus();
        switch ($status) {
            case PhabricatorDaemonLog::STATUS_RUNNING:
                $details = \Yii::t("app",
                    'This daemon is running normally and reported a status update ' .
                    'recently (within %s).',
                    phutil_format_relative_time($unknown_time));
                break;
            case PhabricatorDaemonLog::STATUS_UNKNOWN:
                $details = \Yii::t("app",
                    'This daemon has not reported a status update recently (within %s). ' .
                    'It may have exited abruptly. After %s, it will be presumed dead.',
                    phutil_format_relative_time($unknown_time),
                    phutil_format_relative_time($dead_time));
                break;
            case PhabricatorDaemonLog::STATUS_DEAD:
                $details = \Yii::t("app",
                    'This daemon did not report a status update for %s. It is ' .
                    'presumed dead. Usually, this indicates that the daemon was ' .
                    'killed or otherwise exited abruptly with an error. You may ' .
                    'need to restart it.',
                    phutil_format_relative_time($dead_time));
                break;
            case PhabricatorDaemonLog::STATUS_WAIT:
                $details = \Yii::t("app",
                    'This daemon is running normally and reported a status update ' .
                    'recently (within %s). The process is currently waiting to ' .
                    'restart, either because it is hibernating or because it ' .
                    'encountered an error.',
                    phutil_format_relative_time($unknown_time));
                break;
            case PhabricatorDaemonLog::STATUS_EXITING:
                $details = \Yii::t("app", 'This daemon is shutting down gracefully.');
                break;
            case PhabricatorDaemonLog::STATUS_EXITED:
                $details = \Yii::t("app", 'This daemon exited normally and is no longer running.');
                break;
        }

        $view->addProperty(\Yii::t("app", 'Status Details'), $details);

        $view->addProperty(\Yii::t("app", 'Daemon Class'), $daemon->getDaemon());
        $view->addProperty(\Yii::t("app", 'Host'), $daemon->getHost());
        $view->addProperty(\Yii::t("app", 'PID'), $daemon->getPID());
        $view->addProperty(\Yii::t("app", 'Running as'), $daemon->getRunningAsUser());
        $view->addProperty(\Yii::t("app", 'Started'), OranginsViewUtil::phabricator_datetime($c_epoch, $viewer));
        $view->addProperty(
            \Yii::t("app", 'Seen'),
            \Yii::t("app",
                '%s ago (%s)',
                phutil_format_relative_time(time() - $u_epoch),
                OranginsViewUtil::phabricator_datetime($u_epoch, $viewer)));

        $argv = $daemon->getArgv();
        if (is_array($argv)) {
            $argv = implode("\n", $argv);
        }

        $view->addProperty(
            \Yii::t("app", 'Argv'),
            phutil_tag(
                'textarea',
                array(
                    'style' => 'width: 100%; height: 12em;',
                ),
                $argv));

        $view->addProperty(
            \Yii::t("app", 'View Full Logs'),
            phutil_tag(
                'tt',
                array(),
                "phabricator/ $ ./bin/phd log --id {$id}"));


        return $view;
    }

}
