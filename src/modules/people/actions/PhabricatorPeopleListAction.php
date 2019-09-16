<?php

namespace orangins\modules\people\actions;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\people\capability\PeopleBrowseUserDirectoryCapability;
use orangins\modules\people\query\PhabricatorPeopleSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

/**
 * Class PhabricatorPeopleListAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleListAction
    extends PhabricatorPeopleAction
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
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }

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
        $this->requireApplicationCapability(
            PeopleBrowseUserDirectoryCapability::CAN_VIEW);

        $action = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($request->getURIData('queryKey'))
            ->setSearchEngine(new PhabricatorPeopleSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($action);
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $viewer = $this->getRequest()->getViewer();

        if ($viewer->getIsAdmin()) {
            $crumbs->addAction(
                (new PHUIListItemView())
                    ->setName(\Yii::t("app",'Create New User'))
                    ->setHref($this->getApplicationURI('index/create'))
                    ->setIcon('fa-plus-square'));
        }

        return $crumbs;
    }
}
