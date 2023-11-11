<?php

namespace orangins\modules\home\view;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\AphrontMultiColumnView;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardQueryPanelType;
use PhutilInvalidStateException;
use Yii;

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
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $viewer = $this->getViewer();

        $feed_panel = $this->buildFeedPanel();

        $dashboard = (new AphrontMultiColumnView())
            ->setFluidlayout(true)
            ->setGutter(AphrontMultiColumnView::GUTTER_LARGE);


        $side_panel = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'homepage-side-panel',
            ),
            array(
                $feed_panel,
            ));
        $dashboard->addColumn($side_panel, 'col-lg-12');

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
     * @throws Exception
     * @author 陈妙威
     */
    public function buildFeedPanel()
    {
        $panel = $this->newQueryPanel()
            ->setName(Yii::t("app", 'Recent Activity'))
            ->setProperty('class', 'PhabricatorFeedSearchEngine')
            ->setProperty('key', 'all')
            ->setProperty('limit', 40);

        return $this->renderPanel($panel);
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return PHUIObjectBoxView
     * @throws PhutilInvalidStateException
     * @throws Exception
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
