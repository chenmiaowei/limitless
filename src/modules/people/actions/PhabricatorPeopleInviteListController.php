<?php

namespace orangins\modules\people\actions;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\auth\query\PhabricatorAuthInviteSearchEngine;
use orangins\modules\people\capability\PeopleCreateUsersCapability;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use PhutilURI;

/**
 * Class PhabricatorPeopleInviteListController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleInviteListController
    extends PhabricatorPeopleInviteAction
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
            ->setSearchEngine(new PhabricatorAuthInviteSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($controller);
    }

    /**
     * @param bool $for_app
     * @return \orangins\lib\view\layout\AphrontSideNavFilterView|AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView($for_app = false)
    {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $viewer = $this->getRequest()->getViewer();

        (new PhabricatorAuthInviteSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        return $nav;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $can_invite = $this->hasApplicationCapability(
            PeopleCreateUsersCapability::CAPABILITY);
        $crumbs->addAction(
            (new PHUIListItemView())
                ->setName(\Yii::t("app",'Invite Users'))
                ->setHref($this->getApplicationURI('invite/send/'))
                ->setIcon('fa-plus-square')
                ->setDisabled(!$can_invite)
                ->setWorkflow(!$can_invite));

        return $crumbs;
    }

}
