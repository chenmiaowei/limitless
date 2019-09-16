<?php

namespace orangins\modules\dashboard\actions\panel;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\models\PhabricatorDashboardPanelTransaction;
use orangins\modules\dashboard\query\PhabricatorDashboardPanelTransactionQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\edge\PhabricatorDashboardPanelUsedByObjectEdgeType;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardPanelViewController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelViewController
    extends PhabricatorDashboardController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $panel = PhabricatorDashboardPanel::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$panel) {
            return new Aphront404Response();
        }

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $panel,
            PhabricatorPolicyCapability::CAN_EDIT);

        $title = $panel->getMonogram() . ' ' . $panel->getName();
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            \Yii::t("app",'Panels'),
            $this->getApplicationURI('panel/'));
        $crumbs->addTextCrumb($panel->getMonogram());
        $crumbs->setBorder(true);

        $header = $this->buildHeaderView($panel);
        $curtain = $this->buildCurtainView($panel);

        $usage_box = $this->newUsageView($panel);

        $timeline = $this->buildTransactionTimeline(
            $panel,
            PhabricatorDashboardPanelTransaction::find());

        $rendered_panel = (new PhabricatorDashboardPanelRenderingEngine())
            ->setViewer($viewer)
            ->setPanel($panel)
            ->setContextObject($panel)
            ->setPanelPHID($panel->getPHID())
            ->setParentPanelPHIDs(array())
            ->setEditMode(true)
            ->renderPanel();

        $preview = (new PHUIBoxView())
            ->addClass('dashboard-preview-box')
            ->appendChild($rendered_panel);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(array(
                $rendered_panel,
                $usage_box,
                $timeline,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return mixed
     * @author 陈妙威
     */
    private function buildHeaderView(PhabricatorDashboardPanel $panel)
    {
        $viewer = $this->getViewer();
        $id = $panel->getID();

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setHeader($panel->getName())
            ->setPolicyObject($panel)
            ->setHeaderIcon('fa-window-maximize');

        if (!$panel->getIsArchived()) {
            $header->setStatus('fa-check', 'bluegrey', \Yii::t("app",'Active'));
        } else {
            $header->setStatus('fa-ban', 'red', \Yii::t("app",'Archived'));
        }
        return $header;
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorDashboardPanel $panel)
    {
        $viewer = $this->getViewer();
        $id = $panel->getID();

        $curtain = $this->newCurtainView($panel);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $panel,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Edit Panel'))
                ->setIcon('fa-pencil')
                ->setHref(Url::to([
                    '/dashboard/panel/edit',
                    'id' => $id
                ]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));

        if (!$panel->getIsArchived()) {
            $archive_text = \Yii::t("app",'Archive Panel');
            $archive_icon = 'fa-ban';
        } else {
            $archive_text = \Yii::t("app",'Activate Panel');
            $archive_icon = 'fa-check';
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName($archive_text)
                ->setIcon($archive_icon)
                ->setHref($this->getApplicationURI("panel/archive/{$id}/"))
                ->setDisabled(!$can_edit)
                ->setWorkflow(true));

        return $curtain;
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return mixed
     * @author 陈妙威
     * @throws \PhutilMethodNotImplementedException
     */
    private function newUsageView(PhabricatorDashboardPanel $panel)
    {
        $viewer = $this->getViewer();

        $object_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
            $panel->getPHID(),
            PhabricatorDashboardPanelUsedByObjectEdgeType::EDGECONST);

        if ($object_phids) {
            $handles = $viewer->loadHandles($object_phids);
        } else {
            $handles = array();
        }

        $rows = array();
        foreach ($object_phids as $object_phid) {
            $handle = $handles[$object_phid];

            $icon = $handle->getIcon();

            $rows[] = array(
                (new PHUIIconView())->setIcon($icon),
                $handle->getTypeName(),
                $handle->renderLink(),
            );
        }

        $usage_table = (new AphrontTableView($rows))
            ->setNoDataString(
                \Yii::t("app",
                    'This panel is not used on any dashboard or inside any other ' .
                    'panel container.'))
            ->setColumnClasses(
                array(
                    'center',
                    '',
                    'pri wide',
                ));

        $header_view = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app",'Panel Used By'));

        $usage_box = (new PHUIObjectBoxView())
            ->setTable($usage_table)
            ->setHeader($header_view);

        return $usage_box;
    }
}
