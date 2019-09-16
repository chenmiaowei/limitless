<?php

namespace orangins\modules\dashboard\actions\dashboard;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\install\PhabricatorDashboardInstallWorkflow;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardInstallController
 * @package orangins\modules\dashboard\actions\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardInstallController
    extends PhabricatorDashboardController
{

    /**
     * @var
     */
    private $dashboard;

    /**
     * @param PhabricatorDashboard $dashboard
     * @return $this
     * @author 陈妙威
     */
    public function setDashboard(PhabricatorDashboard $dashboard)
    {
        $this->dashboard = $dashboard;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $dashboard = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$dashboard) {
            return new Aphront404Response();
        }

        $this->setDashboard($dashboard);
        $cancel_uri = $dashboard->getURI();

        $workflow_key = $request->getURIData('workflowKey');

        $workflows = PhabricatorDashboardInstallWorkflow::getAllWorkflows();
        if (!isset($workflows[$workflow_key])) {
            return $this->newWorkflowDialog($dashboard, $workflows);
        }

        /** @var PhabricatorDashboardInstallWorkflow $var */
        $var = clone $workflows[$workflow_key];
        return $var
            ->setRequest($request)
            ->setViewer($viewer)
            ->setDashboard($dashboard)
            ->setMode($request->getURIData('modeKey'))
            ->handleRequest($request);
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @param PhabricatorDashboardInstallWorkflow[] $workflows
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    private function newWorkflowDialog(
        PhabricatorDashboard $dashboard,
        array $workflows)
    {
        $viewer = $this->getViewer();
        $cancel_uri = $dashboard->getURI();

        $menu = (new PHUIObjectItemListView())
            ->setViewer($viewer)
            ->setFlush(true)
            ->setBig(true);

        foreach ($workflows as $key => $workflow) {
            $item = $workflow->getWorkflowMenuItem();
            $item_href = Url::to([
                '/dashboard/index/install',
                'id' => $dashboard->getID(),
                'workflowKey' => $key,
            ]);
            $item->setHref($item_href);
            $menu->addItem($item);
        }

        return $this->newDialog()
            ->addBodyClass('p-0')
            ->setTitle(\Yii::t("app",'Add Dashboard to Menu'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($menu)
            ->addCancelButton($cancel_uri);
    }

}
