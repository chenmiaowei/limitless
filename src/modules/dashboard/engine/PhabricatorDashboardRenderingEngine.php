<?php

namespace orangins\modules\dashboard\engine;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\OranginsObject;
use orangins\lib\view\layout\AphrontMultiColumnView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\dashboard\assets\JavelinDashboardMovePanelBehaviorAsset;
use orangins\modules\dashboard\layoutconfig\PhabricatorDashboardColumn;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\people\models\PhabricatorUser;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardRenderingEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardRenderingEngine extends OranginsObject
{


    /**
     * @var PhabricatorDashboard
     */
    private $dashboard;
    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var
     */
    private $arrangeMode;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

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
     * @return PhabricatorDashboard
     * @author 陈妙威
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @param $mode
     * @return $this
     * @author 陈妙威
     */
    public function setArrangeMode($mode)
    {
        $this->arrangeMode = $mode;
        return $this;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderDashboard()
    {
//        require_celerity_resource('phabricator-dashboard-css');
        $dashboard = $this->getDashboard();
        $viewer = $this->getViewer();

        $is_editable = $this->arrangeMode;

        if ($is_editable) {
            $h_mode = PhabricatorDashboardPanelRenderingEngine::HEADER_MODE_EDIT;
        } else {
            $h_mode = PhabricatorDashboardPanelRenderingEngine::HEADER_MODE_NORMAL;
        }

        $panel_phids = $dashboard->getPanelPHIDs();
        if ($panel_phids) {
            $panels = PhabricatorDashboardPanel::find()
                ->setViewer($viewer)
                ->withPHIDs($panel_phids)
                ->execute();
            $panels = mpull($panels, null, 'getPHID');

            $handles = $viewer->loadHandles($panel_phids);
        } else {
            $panels = array();
            $handles = array();
        }

        $ref_list = $dashboard->getPanelRefList();
        $columns = $ref_list->getColumns();

        $dashboard_id = JavelinHtml::generateUniqueNodeId();

        $result = (new  AphrontMultiColumnView())
            ->setID($dashboard_id)
            ->setFluidLayout(true)
            ->setGutter(AphrontMultiColumnView::GUTTER_LARGE);

        foreach ($columns as $column) {
            $column_views = array();
            $panelRefs = $column->getPanelRefs();
            foreach ($panelRefs as $panel_ref) {
                $panel_phid = $panel_ref->getPanelPHID();

                $panel_engine = (new  PhabricatorDashboardPanelRenderingEngine())
                    ->setViewer($viewer)
                    ->setEnableAsyncRendering(true)
                    ->setContextObject($dashboard)
                    ->setPanelKey($panel_ref->getPanelKey())
                    ->setPanelPHID($panel_phid)
                    ->setParentPanelPHIDs(array())
                    ->setHeaderMode($h_mode)
                    ->setEditMode($is_editable)
                    ->setPanelHandle($handles[$panel_phid]);

                $panel = ArrayHelper::getValue($panels, $panel_phid);
                if ($panel) {
                    $panel_engine->setPanel($panel);
                }

                $column_views[] = $panel_engine->renderPanel();
            }

            $column_classes = $column->getClasses();

            if ($is_editable) {
                $column_views[] = $this->renderAddPanelPlaceHolder();
                $column_views[] = $this->renderAddPanelUI($column);
            }

            $sigil = 'dashboard-column';

            $metadata = array(
                'columnKey' => $column->getColumnKey(),
            );

            $result->addColumn(
                $column_views,
                implode(' ', $column_classes),
                $sigil,
                $metadata);
        }

        if ($is_editable) {
            $move_uri = Url::to([
                '/dashboard/index/adjust',
                'op' => 'move',
                'contextPHID' => $dashboard->getPHID(),
            ]);

            JavelinHtml ::initBehavior(
                new JavelinDashboardMovePanelBehaviorAsset(),
                array(
                    'dashboardNodeID' => $dashboard_id,
                    'moveURI' => (string)$move_uri,
                ));
        }

        $view = (new PHUIBoxView())
            ->addClass('dashboard-view')
            ->appendChild(
                array(
                    $result,
                ));

        return $view;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    private function renderAddPanelPlaceHolder()
    {
        return JavelinHtml::phutil_tag('div', [
            'class' => 'text-center p-3 border-2 border-grey-300 rounded drag-ghost dashboard-panel-placeholder',
            'sigil' => 'workflow',
        ], JavelinHtml::phutil_tag(
            'span',
            array(),
            \Yii::t("app",'This column does not have any panels yet.')));
    }

    /**
     * @param PhabricatorDashboardColumn $column
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderAddPanelUI(PhabricatorDashboardColumn $column)
    {
        $dashboard = $this->getDashboard();
        $column_key = $column->getColumnKey();

        $create_uri = Url::to([
            '/dashboard/panel/edit',
            'contextPHID' => $dashboard->getPHID(),
            'columnKey' => $column_key
        ]);

        $add_uri = Url::to([
            '/dashboard/index/adjust',
            'op' => 'add',
            'contextPHID' => $dashboard->getPHID(),
            'columnKey' => $column_key
        ]);

        $create_button = (new  PHUIButtonView())
            ->setTag('a')
            ->setHref($create_uri)
            ->setWorkflow(true)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setText(\Yii::t("app",'Create Panel'))
            ->addClass('btn-xs')
            ->addClass(PHUI::MARGIN_MEDIUM);

        $add_button = (new  PHUIButtonView())
            ->setTag('a')
            ->setHref($add_uri)
            ->setWorkflow(true)
            ->setText(\Yii::t("app",'Add Existing Panel'))
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->addClass('btn-xs')
            ->addClass(PHUI::MARGIN_MEDIUM);

        return phutil_tag(
            'div',
            array(
                'style' => 'text-align: center;',
            ),
            array(
                $create_button,
                $add_button,
            ));
    }
}
