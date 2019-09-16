<?php

namespace orangins\modules\dashboard\actions;

use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITwoColumnView;

/**
 * Class PhabricatorDashboardConsoleController
 * @package orangins\modules\dashboard\actions
 * @author 陈妙威
 */
final class PhabricatorDashboardConsoleController
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
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $menu = (new PHUIObjectItemListView())
            ->setUser($viewer)
            ->setBig(true);

        $menu->addItem(
            (new PHUIObjectItemView())
                ->setHeader(\Yii::t("app",'Portals'))
                ->setImageIcon('fa-compass')
                ->setHref('/portal/')
                ->setClickable(true)
                ->addAttribute(
                    \Yii::t("app",
                        'Portals are collections of dashboards, links, and other ' .
                        'resources that can provide a high-level overview of a ' .
                        'project.')));

        $menu->addItem(
            (new PHUIObjectItemView())
                ->setHeader(\Yii::t("app",'Dashboards'))
                ->setImageIcon('fa-dashboard')
                ->setHref($this->getApplicationURI('/'))
                ->setClickable(true)
                ->addAttribute(
                    \Yii::t("app",
                        'Dashboards organize panels, creating a cohesive page for ' .
                        'analysis or action.')));

        $menu->addItem(
            (new PHUIObjectItemView())
                ->setHeader(\Yii::t("app",'Panels'))
                ->setImageIcon('fa-line-chart')
                ->setHref($this->getApplicationURI('panel/'))
                ->setClickable(true)
                ->addAttribute(
                    \Yii::t("app",
                        'Panels show queries, charts, and other information to provide ' .
                        'insight on a particular topic.')));

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app",'Console'));
        $crumbs->setBorder(true);

        $title = \Yii::t("app",'Dashboard Console');

        $box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setObjectList($menu);

        $view = (new PHUITwoColumnView())
            ->setFixed(true)
            ->setFooter($box);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
