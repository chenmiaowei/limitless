<?php

namespace orangins\modules\favorites\engineextension;

use orangins\lib\PhabricatorApplication;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\page\menu\PhabricatorMainMenuBarExtension;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\favorites\application\PhabricatorFavoritesApplication;
use orangins\modules\favorites\engine\PhabricatorFavoritesProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorFavoritesMainMenuBarExtension
 * @package orangins\modules\favorites\engineextension
 * @author 陈妙威
 */
final class PhabricatorFavoritesMainMenuBarExtension
    extends PhabricatorMainMenuBarExtension
{

    /**
     *
     */
    const MAINMENUBARKEY = 'favorites';

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function isExtensionEnabledForViewer(PhabricatorUser $viewer)
    {
        return PhabricatorApplication::isClassInstalledForViewer(
            PhabricatorFavoritesApplication::className(),
            $viewer);
    }

    public function isExtensionEnabled()
    {
        return false;
    }


    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 1300;
    }

    /**
     * @return array|mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function buildMainMenus()
    {
        $viewer = $this->getViewer();

        $dropdown = $this->newDropdown($viewer);
        if (!$dropdown) {
            return array();
        }

        $favorites_menu = (new PHUIButtonView())
            ->setTag('a')
            ->setHref('#')
            ->setIcon('fa-bookmark')
            ->addClass('navbar-nav-link dropdown-toggle')
            ->addClass('phabricator-core-user-menu')
            ->setNoCSS(true)
            ->setDropdown(true)
            ->setDropdownMenu($dropdown)
            ->setAuralLabel(pht('Favorites Menu'));

        return array(
            $favorites_menu,
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @return null|PhabricatorActionListView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */
    private function newDropdown(PhabricatorUser $viewer)
    {
        $applications = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withClasses(array('PhabricatorFavoritesApplication'))
            ->withInstalled(true)
            ->execute();
        $favorites = head($applications);
        if (!$favorites) {
            return null;
        }

        $menu_engine = (new PhabricatorFavoritesProfileMenuEngine())
            ->setViewer($viewer)
            ->setProfileObject($favorites)
            ->setCustomPHID($viewer->getPHID());

        $action = $this->getAction();
        if ($action) {
            $menu_engine->setAction($action);
        }

        $filter_view = $menu_engine
            ->newProfileMenuItemViewList()
            ->newNavigationView();

        $menu_view = $filter_view->getMenu();
        $item_views = $menu_view->getItems();

        $view = (new PhabricatorActionListView())
            ->setViewer($viewer);
        foreach ($item_views as $item) {
            $action = (new PhabricatorActionView())
                ->setName($item->getName())
                ->setHref($item->getHref())
                ->setIcon($item->getIcon())
                ->setType($item->getType());
            $view->addAction($action);
        }

        return $view;
    }

}
