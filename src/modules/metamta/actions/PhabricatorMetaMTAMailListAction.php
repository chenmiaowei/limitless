<?php

namespace orangins\modules\metamta\actions;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\metamta\query\PhabricatorMetaMTAMailSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use PhutilURI;

/**
 * Class PhabricatorMetaMTAMailListAction
 * @package orangins\modules\metamta\actions
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailListAction
    extends PhabricatorMetaMTAAction
{

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
        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($request->getURIData('queryKey'))
            ->setSearchEngine(new PhabricatorMetaMTAMailSearchEngine())
            ->setNavigation($this->buildSideNav());

        return $this->delegateToAction($controller);
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNav()
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorMetaMTAMailSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return null|\orangins\lib\view\phui\PHUIListView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNav()->getMenu();
    }

}
