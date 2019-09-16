<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\phid\PhabricatorPHIDConstants;

/**
 * Class PhabricatorConfigHistoryAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigHistoryAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $xactions = PhabricatorConfigTransaction::find()
            ->setViewer($viewer)
            ->needComments(true)
            ->execute();

        $object = new PhabricatorConfigEntry();

        $xaction = $object->getApplicationTransactionTemplate();

        $view = $xaction->getApplicationTransactionViewObject();

        $timeline = $view
            ->setUser($viewer)
            ->setTransactions($xactions)
            ->setRenderAsFeed(true)
            ->setObjectPHID(PhabricatorPHIDConstants::PHID_VOID);

        $timeline->setShouldTerminate(true);

        $object->willRenderTimeline($timeline, $this->getRequest());

        $title = \Yii::t("app",'Settings History');
        $header = $this->buildHeaderView($title);

        $nav = $this->buildSideNavView();
        $nav->selectFilter('history/');

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($timeline);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

}
