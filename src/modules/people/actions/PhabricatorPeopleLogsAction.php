<?php

namespace orangins\modules\people\actions;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\people\query\PhabricatorPeopleLogSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use PhutilURI;

/**
 * Class PhabricatorPeopleLogsAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleLogsAction
    extends PhabricatorPeopleAction
{

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($request->getURIData('queryKey'))
            ->setSearchEngine(new PhabricatorPeopleLogSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($controller);
    }

    /**
     * @param bool $for_app
     * @return \orangins\lib\view\layout\AphrontSideNavFilterView|AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSideNavView($for_app = false)
    {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $viewer = $this->getRequest()->getViewer();

        (new PhabricatorPeopleLogSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        return $nav;
    }

}
