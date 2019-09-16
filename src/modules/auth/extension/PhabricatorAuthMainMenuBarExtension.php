<?php

namespace orangins\modules\auth\extension;

use orangins\modules\auth\actions\PhabricatorAuthAction;
use orangins\lib\view\page\menu\PhabricatorMainMenuBarExtension;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilURI;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthMainMenuBarExtension
 * @package orangins\modules\auth\extension
 * @author 陈妙威
 */
final class PhabricatorAuthMainMenuBarExtension
    extends PhabricatorMainMenuBarExtension
{
    /**
     *
     */
    const MAINMENUBARKEY = 'auth';

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function isExtensionEnabledForViewer(PhabricatorUser $viewer)
    {
        return true;
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
        return 900;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function buildMainMenus()
    {
        $viewer = $this->getViewer();

        if ($viewer->isLoggedIn()) {
            return array();
        }

        $controller = $this->getAction();
        if ($controller instanceof PhabricatorAuthAction) {
            // Don't show the "Login" item on auth controllers, since they're
            // generally all related to logging in anyway.
            return array();
        }

        return array(
            $this->buildLoginMenu(),
        );
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    private function buildLoginMenu()
    {
        $action = $this->getAction();

        $params = ['/auth/index/start'];
        if ($action) {
            $path = $action->getRequest()->getPath();
            $params['next'] = $path;
        }

        return (new PHUIButtonView())
            ->setTag('a')
            ->setText(\Yii::t("app", 'Log In'))
            ->setHref(Url::to($params))
            ->setNoCSS(true)
            ->addClass('phabricator-core-login-button');
    }

}
