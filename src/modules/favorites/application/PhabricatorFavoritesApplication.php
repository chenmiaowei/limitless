<?php

namespace orangins\modules\favorites\application;

use orangins\lib\PhabricatorApplication;

/**
 * Class PhabricatorFavoritesApplication
 * @package orangins\modules\favorites\application
 * @author 陈妙威
 */
final class PhabricatorFavoritesApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\favorites\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'favorites';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return pht('Favorites');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return pht('Favorite Items');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-bookmark';
    }

//    /**
//     * @return array
//     * @author 陈妙威
//     */
//    public function getRoutes()
//    {
//        return array(
//            '/favorites/' => array(
//                '' => 'PhabricatorFavoritesMenuItemController',
//                'menu/' => $this->getProfileMenuRouting(
//                    'PhabricatorFavoritesMenuItemController'),
//            ),
//        );
//    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        return false;
    }
}
