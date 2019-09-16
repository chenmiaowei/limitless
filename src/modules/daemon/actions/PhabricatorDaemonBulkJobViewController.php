<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;

/**
 * Class PhabricatorDaemonBulkJobViewController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonBulkJobViewController
    extends PhabricatorDaemonBulkJobController
{

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $job = PhabricatorWorkerBulkJob::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$job) {
            return new Aphront404Response();
        }

        $title = \Yii::t("app", 'Bulk Job {0}', [$job->getID()]);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $properties = $this->renderProperties($job);
        $curtain = $this->buildCurtainView($job);

        $box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Details'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addPropertyList($properties);

        $timeline = $this->buildTransactionTimeline(
            $job,
            PhabricatorWorkerBulkJobTransaction::find());
        $timeline->setShouldTerminate(true);

        $header = (new PHUIHeaderView())
            ->setHeader($title)
            ->setHeaderIcon('fa-hourglass');

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setCurtain($curtain)
            ->setMainColumn(array(
                $box,
                $timeline,
            ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return PHUIPropertyListView
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderProperties(PhabricatorWorkerBulkJob $job)
    {
        $viewer = $this->getViewer();

        $view = (new PHUIPropertyListView())
            ->setUser($viewer)
            ->setObject($job);

        $view->addProperty(\Yii::t("app", 'Author'), $viewer->renderHandle($job->getAuthorPHID()));

        $view->addProperty(\Yii::t("app", 'Status'), $job->getStatusName());

        return $view;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorWorkerBulkJob $job)
    {
        $viewer = $this->getViewer();
        $curtain = $this->newCurtainView($job);

        foreach ($job->getCurtainActions($viewer) as $action) {
            $curtain->addAction($action);
        }

        return $curtain;
    }

}
