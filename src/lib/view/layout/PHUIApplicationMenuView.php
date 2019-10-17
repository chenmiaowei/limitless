<?php

namespace orangins\lib\view\layout;

use Exception;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;

/**
 * Class PHUIApplicationMenuView
 * @package orangins\lib\view
 * @author 陈妙威
 */
final class PHUIApplicationMenuView extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $crumbs;
    /**
     * @var
     */
    private $searchEngine;
    /**
     * @var
     */
    private $profileMenu;

    /**
     * @var array
     */
    private $items = array();

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $name
     * @return PHUIApplicationMenuView
     * @author 陈妙威
     */
    public function addLabel($name)
    {
        $item = (new PHUIListItemView())
            ->setName($name);

        return $this->addItem($item);
    }

    /**
     * @param $name
     * @param $href
     * @return PHUIApplicationMenuView
     * @author 陈妙威
     */
    public function addLink($name, $href)
    {
        $item = (new PHUIListItemView())
            ->setName($name)
            ->setHref($href);

        return $this->addItem($item);
    }

    /**
     * @param AphrontSideNavFilterView $nav
     * @return $this
     * @author 陈妙威
     */
    public function setProfileMenu(
        AphrontSideNavFilterView $nav)
    {
        $this->profileMenu = $nav;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProfileMenu()
    {
        return $this->profileMenu;
    }

    /**
     * @param PHUIListItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem(PHUIListItemView $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function setSearchEngine(PhabricatorApplicationSearchEngine $engine)
    {
        $this->searchEngine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSearchEngine()
    {
        return $this->searchEngine;
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(PHUICrumbsView $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function buildListView()
    {
        $viewer = $this->getViewer();

        $view = (new PHUIListView())
            ->setViewer($viewer);

        $profile_menu = $this->getProfileMenu();
        if ($profile_menu) {
            foreach ($profile_menu->getMenu()->getItems() as $item) {
                if ($item->getHideInApplicationMenu()) {
                    continue;
                }

                $item = clone $item;
                $view->addMenuItem($item);
            }
        }

        $crumbs = $this->getCrumbs();
        if ($crumbs) {
            $actions = $crumbs->getActions();
            if ($actions) {
                $view->newLabel(\Yii::t("app",'Create'));
                foreach ($crumbs->getActions() as $action) {
                    $view->addMenuItem($action);
                }
            }
        }

        $engine = $this->getSearchEngine();
        if ($engine) {
            $engine
                ->setViewer($viewer)
                ->addNavigationItems($view);
        }

        foreach ($this->items as $item) {
            $view->addMenuItem($item);
        }

        return $view;
    }

}
