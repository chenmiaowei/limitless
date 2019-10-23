<?php

namespace orangins\modules\people\engineextension;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\page\menu\PhabricatorMainMenuBarExtension;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\people\assets\JavelinUserMenuTimezoneAsset;
use orangins\modules\people\models\PhabricatorUser;
use ReflectionException;
use Yii;
use yii\helpers\Url;

/**
 * Class PeopleMainMenuBarExtension
 * @author 陈妙威
 */
final class PeopleMainMenuBarExtension extends PhabricatorMainMenuBarExtension
{

    /**
     *
     */
    const MAINMENUBARKEY = 'user';

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function isExtensionEnabledForViewer(PhabricatorUser $viewer)
    {
        return $viewer->isLoggedIn();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireFullSession()
    {
        return false;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 1200;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     * @throws ReflectionException
     */
    public function buildMainMenus()
    {
        $viewer = $this->getViewer();
        $application = $this->getApplication();


        $peopleCustomMainMenuBarExtensions = PeopleCustomMainMenuBarExtension::getAllExtensions();
        if(count($peopleCustomMainMenuBarExtensions) > 0) {
            /** @var PeopleCustomMainMenuBarExtension $wild */
            $wild = head($peopleCustomMainMenuBarExtensions);
            $user_menu = $wild->newMainMenus($viewer, $this->getIsFullSession(), $application);
        } else {
            $user_menu = $this->newDefaultMainMenus($viewer, $application);

        }
        return array(
            $user_menu,
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorApplication $application
     * @return PHUIButtonView
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function newDefaultMainMenus(
        PhabricatorUser $viewer,
        $application)
    {
        $image = $viewer->getProfileImageURI();
        $profile_image = (new PHUIIconView())
            ->setImage($image)
            ->setHeadSize(PHUIIconView::HEAD_SMALL);


        $user_menu = (new PHUIButtonView())
            ->setTag('a')
            ->setHref(Url::to(['/people/index/view', 'username' => $viewer->getUsername()]))
            ->setIcon($profile_image)
            ->addClass('navbar-nav-link dropdown-toggle')
            ->addClass('phabricator-core-user-menu')
            ->setNoCSS(true)
            ->setAuralLabel(Yii::t("app", 'Account Menu'));


        $person_to_show = (new PHUIObjectItemView())
            ->addClass('p-0 border-0')
            ->setObjectName($viewer->getRealName())
            ->setSubHead($viewer->getUsername())
            ->setImageURI($viewer->getProfileImageURI());

        $user_view = (new PHUIObjectItemListView())
            ->setViewer($viewer)
            ->setFlush(true)
            ->setSimple(true)
            ->addItem($person_to_show)
            ->addClass('phabricator-core-user-profile-object');

        $view = (new PhabricatorActionListView())
            ->setViewer($viewer);

        if ($this->getIsFullSession()) {
            $view->addAction(
                (new PhabricatorActionView())
                    ->appendChild($user_view));

            $view->addAction(
                (new PhabricatorActionView())
                    ->setType(PhabricatorActionView::TYPE_DIVIDER));

            $view->addAction(
                (new PhabricatorActionView())
                    ->setName(Yii::t("app", 'Profile'))
                    ->setHref(Url::to(['/people/index/view', 'id' => $viewer->getID()])));

            $view->addAction(
                (new PhabricatorActionView())
                    ->setName(Yii::t("app", 'Settings'))
                    ->setHref(Url::to(['/settings/index/user', 'username' => $viewer->getUsername()])));

            $view->addAction(
                (new PhabricatorActionView())
                    ->setName(Yii::t("app", 'Manage'))
                    ->setHref(Url::to(['/people/index/manage', 'id' => $viewer->getID()])));

            if ($application) {
                $help_links = $application->getHelpMenuItems($viewer);
                if ($help_links) {
                    foreach ($help_links as $link) {
                        $view->addAction($link);
                    }
                }
            }

            $view->addAction(
                (new PhabricatorActionView())
                    ->addSigil('logout-item')
                    ->setType(PhabricatorActionView::TYPE_DIVIDER));
        }

        $view->addAction(
            (new PhabricatorActionView())
                ->setName(Yii::t("app", 'Log Out {0}', [$viewer->getUsername()]))
                ->addSigil('logout-item')
                ->setHref(Url::to(['/auth/index/logout']))
                ->setWorkflow(true));

        JavelinHtml::initBehavior(
            new JavelinUserMenuTimezoneAsset(),
            array(
                'menuID' => $user_menu->getID(),
                'menu' => $view->getDropdownMenuMetadata(),
            ));

        return $user_menu;
    }
}
