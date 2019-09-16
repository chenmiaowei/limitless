<?php

namespace orangins\modules\dashboard\actions;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\dashboard\editors\PhabricatorDashboardEditEngine;
use orangins\modules\dashboard\query\PhabricatorDashboardSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use PhutilURI;

/**
 * Class PhabricatorDashboardListController
 * @package orangins\modules\dashboard\actions
 * @author 陈妙威
 */
final class PhabricatorDashboardListController
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
     * @return mixed
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
        $query_key = $request->getURIData('queryKey');

        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($query_key)
            ->setSearchEngine(new PhabricatorDashboardSearchEngine())
            ->setNavigation($this->buildSideNavView());
        return $this->delegateToAction( $controller);
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView()
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorDashboardSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        (new PhabricatorDashboardEditEngine())
            ->setViewer($this->getViewer())
            ->addActionToCrumbs($crumbs);

        return $crumbs;
    }

}
