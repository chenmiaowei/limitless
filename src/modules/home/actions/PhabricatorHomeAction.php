<?php

namespace orangins\modules\home\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;

/**
 * Class PhabricatorHomeController
 * @package orangins\modules\home\actions
 * @author 陈妙威
 */
abstract class PhabricatorHomeAction extends PhabricatorAction
{

    /**
     * @var
     */
    private $home;
    /**
     * @var
     */
    private $profileMenu;

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        $menu = $this->controller->newApplicationMenu();

        $profile_menu = $this->getProfileMenu();
        if ($profile_menu) {
            $menu->setProfileMenu($profile_menu);
        }

        return $menu;
    }

    /**
     * @return null
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getProfileMenu()
    {
        if (!$this->profileMenu) {
            $viewer = $this->getViewer();
            $applications = (new PhabricatorApplicationQuery())
                ->setViewer($viewer)
                ->withShortName(false)
                ->withClasses(array(PhabricatorApplicationSearchEngine::class))
                ->withInstalled(true)
                ->execute();
            $home = head($applications);
            if (!$home) {
                return null;
            }

            $engine = (new PhabricatorHomeProfileMenuEngine())
                ->setViewer($viewer)
                ->setProfileObject($home)
                ->setCustomPHID($viewer->getPHID());

            $this->profileMenu = $engine->buildNavigation();
        }

        return $this->profileMenu;
    }

}
