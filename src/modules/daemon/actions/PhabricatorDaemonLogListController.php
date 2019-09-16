<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\view\PhabricatorDaemonLogListView;

final class PhabricatorDaemonLogListController
    extends PhabricatorDaemonController
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $pager = new AphrontCursorPagerView();
        $pager->readFromRequest($request);

        $logs = PhabricatorDaemonLog::find()
            ->setViewer($viewer)
            ->setAllowStatusWrites(true)
            ->executeWithCursorPager($pager);

        $daemon_table = (new PhabricatorDaemonLogListView())
            ->setViewer($viewer)
            ->setDaemonLogs($logs);

        $box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'All Daemons'))
            ->setTable($daemon_table);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'All Daemons'));

        $nav = $this->buildSideNavView();
        $nav->selectFilter('log');
        $nav->setCrumbs($crumbs);
        $nav->appendChild($box);
        $nav->appendChild($pager);

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'All Daemons'))
            ->appendChild($nav);
    }
}
