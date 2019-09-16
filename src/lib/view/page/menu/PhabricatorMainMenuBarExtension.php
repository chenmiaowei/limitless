<?php

namespace orangins\lib\view\page\menu;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\OranginsObject;
use orangins\lib\PhabricatorApplication;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorMainMenuBarExtension
 * @package orangins\lib\view\page\menu
 * @author 陈妙威
 */
abstract class PhabricatorMainMenuBarExtension extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var
     */
    private $application;
    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $isFullSession;

    /**
     * @return PhabricatorAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param PhabricatorAction $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return static
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorApplication $application
     * @return $this
     * @author 陈妙威
     */
    public function setApplication(PhabricatorApplication $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplication()
    {
        return $this->application;
    }


    /**
     * @param $is_full_session
     * @return $this
     * @author 陈妙威
     */
    public function setIsFullSession($is_full_session)
    {
        $this->isFullSession = $is_full_session;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsFullSession()
    {
        return $this->isFullSession;
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('MAINMENUBARKEY');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireFullSession()
    {
        return true;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function isExtensionEnabledForViewer(PhabricatorUser $viewer)
    {
        if (!$viewer->isLoggedIn()) {
            return false;
        }

        if (!$viewer->isUserActivated()) {
            return false;
        }

        // Don't show menus for users with partial sessions. This usually means
        // they have logged in but have not made it through MFA, so we don't want
        // to show notification counts, saved queries, etc.
        if (!$viewer->hasSession()) {
            return false;
        }

        if ($viewer->getSession()->getIsPartial()) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 1000;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function buildMainMenus();

    /**
     * @return PhabricatorMainMenuBarExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorMainMenuBarExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->setSortMethod('getExtensionOrder')
            ->execute();
    }

    /**
     * @return PhabricatorMainMenuBarExtension[]
     * @author 陈妙威
     */
    final public static function getAllEnabledExtensions()
    {
        $extensions = self::getAllExtensions();
        foreach ($extensions as $key => $extension) {
            if (!$extension->isExtensionEnabled()) {
                unset($extensions[$key]);
            }
        }

        return $extensions;
    }

}
