<?php

namespace orangins\modules\home\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\AphrontMultiColumnView;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardQueryPanelType;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;

/**
 * Class PHUIHomeView
 * @package orangins\modules\home\view
 * @author 陈妙威
 */
final class PHUIHomeView extends AphrontTagView
{

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array();
    }

    /**
     * @return PHUIBoxView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $viewer = $this->getViewer();

        $revision_panel = null;
//        if ($has_differential) {
//            $revision_panel = $this->buildRevisionPanel();
//        }

        $tasks_panel = null;
//        if ($has_maniphest) {
//            $tasks_panel = $this->buildTasksPanel();
//        }

        $repository_panel = null;
//        if ($has_diffusion) {
//            $repository_panel = $this->buildRepositoryPanel();
//        }

        $feed_panel = $this->buildFeedPanel();

        $dashboard = (new AphrontMultiColumnView())
            ->setFluidlayout(true)
            ->setGutter(AphrontMultiColumnView::GUTTER_LARGE);


        $main_panel = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'homepage-panel',
            ),
            array(
                $revision_panel,
                $tasks_panel,
                $repository_panel,
            ));
        $dashboard->addColumn($main_panel, 'col-lg-8');

        $side_panel = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'homepage-side-panel',
            ),
            array(
                $feed_panel,
            ));
        $dashboard->addColumn($side_panel, 'col-lg-4');

        $view = (new PHUIBoxView())
            ->addClass('dashboard-view')
            ->appendChild($dashboard);

        return $view;
    }


    /**
     * @return PhabricatorDashboardPanel
     * @author 陈妙威
     */
    private function newQueryPanel()
    {
        $panel_type = (new PhabricatorDashboardQueryPanelType())
            ->getPanelTypeKey();

        return (new PhabricatorDashboardPanel())
            ->setPanelType($panel_type);
    }

    /**
     * @return PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildFeedPanel()
    {
        $panel = $this->newQueryPanel()
            ->setName(\Yii::t("app", 'Recent Activity'))
            ->setProperty('class', 'PhabricatorFeedSearchEngine')
            ->setProperty('key', 'all')
            ->setProperty('limit', 40);

        return $this->renderPanel($panel);
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return PHUIObjectBoxView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderPanel(PhabricatorDashboardPanel $panel)
    {
        $viewer = $this->getViewer();

        return (new PhabricatorDashboardPanelRenderingEngine())
            ->setViewer($viewer)
            ->setPanel($panel)
            ->setParentPanelPHIDs(array())
            ->renderPanel();
    }
}
