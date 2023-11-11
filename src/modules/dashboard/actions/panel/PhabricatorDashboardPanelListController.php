<?php

namespace orangins\modules\dashboard\actions\panel;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\query\PhabricatorDashboardPanelSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use PhutilURI;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardPanelListController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelListController
    extends PhabricatorDashboardController
{

    /**
     * @var
     */
    private $queryKey;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $query_key = $request->getURIData('queryKey');

        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($query_key)
            ->setSearchEngine(new PhabricatorDashboardPanelSearchEngine())
            ->setNavigation($this->buildSideNavView());
        return $this->delegateToController($controller);
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildSideNavView()
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorDashboardPanelSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addTextCrumb(\Yii::t("app",'Panels'), $this->getApplicationURI() . 'panel/');

        $crumbs->addAction(
            (new PHUIListItemView())
                ->setIcon('fa-plus-square')
                ->setName(\Yii::t("app",'Create Panel'))
                ->setHref(Url::to(['/dashboard/panel/edit'])));

        return $crumbs;
    }

    /**
     * @return PHUIBigInfoView
     * @author 陈妙威
     */
    protected function getNewUserBody()
    {
        $create_button = (new PHUIButtonView())
            ->setTag('a')
            ->setText(\Yii::t("app",'Create a Panel'))
            ->setHref(Url::to(['/dashboard/panel/edit']))
            ->setColor(PHUIButtonView::GREEN);

        $icon = $this->getApplication()->getIcon();
        $app_name = $this->getApplication()->getName();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(\Yii::t("app",'Welcome to %s', $app_name))
            ->setDescription(
                \Yii::t("app",'Build individual panels to display on your homepage dashboard.'))
            ->addAction($create_button);

        return $view;
    }

}
