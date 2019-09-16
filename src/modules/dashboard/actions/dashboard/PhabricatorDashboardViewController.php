<?php

namespace orangins\modules\dashboard\actions\dashboard;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\dashboard\actions\PhabricatorDashboardProfileController;
use orangins\modules\dashboard\engine\PhabricatorDashboardRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardViewController
 * @package orangins\modules\dashboard\actions\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardViewController
    extends PhabricatorDashboardProfileController
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
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
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

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $dashboard,
            PhabricatorPolicyCapability::CAN_EDIT);

        $title = $dashboard->getName();
        $crumbs = $this->buildApplicationCrumbs();
        $header = $this->buildHeaderView();

        $curtain = $this->buildCurtainView($dashboard);

        $usage_box = $this->newUsageView($dashboard);

        $timeline = $this->buildTransactionTimeline(
            $dashboard,
            PhabricatorDashboardTransaction::find());
        $timeline->setShouldTerminate(true);

        $rendered_dashboard = (new PhabricatorDashboardRenderingEngine())
            ->setViewer($viewer)
            ->setDashboard($dashboard)
            ->setArrangeMode($can_edit)
            ->renderDashboard();

        $dashboard_box = (new PHUIBoxView())
            ->addClass('dashboard-preview-box')
            ->appendChild($rendered_dashboard);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $dashboard_box,
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
     * @param PhabricatorDashboard $dashboard
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorDashboard $dashboard)
    {
        $viewer = $this->getViewer();
        $id = $dashboard->getID();

        $curtain = $this->newCurtainView($dashboard);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $dashboard,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Edit Dashboard'))
                ->setIcon('fa-pencil')
                ->setHref(Url::to(['/dashboard/index/edit', 'id' => $id]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Add Dashboard to Menu'))
                ->setIcon('fa-wrench')
                ->setHref(Url::to(['/dashboard/index/install', 'id' => $id]))
                ->setWorkflow(true));

        if ($dashboard->isArchived()) {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'Activate Dashboard'))
                    ->setIcon('fa-check')
                    ->setHref(Url::to(['/dashboard/index/archive', 'id' => $id]))
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true));
        } else {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'Archive Dashboard'))
                    ->setIcon('fa-ban')
                    ->setHref(Url::to(['/dashboard/index/archive', 'id' => $id]))
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true));
        }

        return $curtain;
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @return PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    private function newUsageView(PhabricatorDashboard $dashboard)
    {
        $viewer = $this->getViewer();

        $custom_phids = array();
        if ($viewer->getPHID()) {
            $custom_phids[] = $viewer->getPHID();
        }

        /** @var PhabricatorProfileMenuItemConfiguration[] $items */
        $items = PhabricatorProfileMenuItemConfiguration::find()
            ->setViewer($viewer)
            ->withAffectedObjectPHIDs(
                array(
                    $dashboard->getPHID(),
                ))
            ->withCustomPHIDs($custom_phids, $include_global = true)
            ->execute();

        $handle_phids = array();
        foreach ($items as $item) {
            $handle_phids[] = $item->getProfilePHID();
            $custom_phid = $item->getCustomPHID();
            if ($custom_phid) {
                $handle_phids[] = $custom_phid;
            }
        }

        if ($handle_phids) {
            $handles = $viewer->loadHandles($handle_phids);
        } else {
            $handles = array();
        }

        $items = msortv($items, 'newUsageSortVector');

        $rows = array();
        foreach ($items as $item) {
            $profile_phid = $item->getProfilePHID();
            $custom_phid = $item->getCustomPHID();

            $profile = $handles[$profile_phid]->renderLink();
            $profile_icon = $handles[$profile_phid]->getIcon();

            if ($custom_phid) {
                $custom = $handles[$custom_phid]->renderLink();
            } else {
                $custom = \Yii::t("app",'Global');
            }

            $type = $item->getProfileMenuTypeDescription();

            $rows[] = array(
                (new PHUIIconView())->setIcon($profile_icon),
                $type,
                $profile,
                $custom,
            );
        }

        $usage_table = (new AphrontTableView($rows))
            ->setNoDataString(
                \Yii::t("app",'This dashboard has not been added to any menus.'))
            ->setHeaders(
                array(
                    null,
                    \Yii::t("app",'Type'),
                    \Yii::t("app",'Menu'),
                    \Yii::t("app",'Global/Personal'),
                ))
            ->setColumnClasses(
                array(
                    'center',
                    null,
                    'pri',
                    'wide',
                ));

        $header_view = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app",'Dashboard Used By'));

        $usage_box = (new PHUIObjectBoxView())
            ->setTable($usage_table)
            ->setHeader($header_view);

        return $usage_box;
    }


}
