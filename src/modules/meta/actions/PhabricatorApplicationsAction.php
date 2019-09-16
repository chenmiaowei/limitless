<?php

namespace orangins\modules\meta\actions;

use orangins\lib\actions\PhabricatorAction;
use PhutilURI;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\meta\query\PhabricatorAppSearchEngine;

/**
 * Class PhabricatorApplicationsAction
 * @package orangins\modules\meta\actions
 * @author 陈妙威
 */
abstract class PhabricatorApplicationsAction extends PhabricatorAction
{

    /**
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildSideNavView($for_app = false)
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorAppSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return null
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView(true)->getMenu();
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @param PhabricatorApplication $application
     * @author 陈妙威
     */
    protected function addApplicationCrumb(
        PHUICrumbsView $crumbs,
        PhabricatorApplication $application)
    {

        $crumbs->addTextCrumb(
            $application->getName(),
            '/applications/view/' . get_class($application) . '/');
    }

}
