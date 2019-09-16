<?php

namespace orangins\modules\home\actions;

use orangins\modules\home\application\PhabricatorHomeApplication;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;

/**
 * Class PhabricatorHomeMenuItemController
 * @package orangins\modules\home\actions
 * @author 陈妙威
 */
final class PhabricatorHomeMenuItemAction extends PhabricatorHomeAction
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
    public function isGlobalDragAndDropUploadEnabled()
    {
        return true;
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 陈妙威
     */
    public function run( )
    {

        $request = $this->controller->getRequest();
        $viewer = $this->getViewer();

        // Test if we should show mobile users the menu or the page content:
        // if you visit "/", you just get the menu. If you visit "/home/", you
        // get the content.
        $is_content = $request->getURIData('content');

        $home_app = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withShortName(false)
            ->withClasses(array(PhabricatorHomeApplication::class))
            ->withInstalled(true)
            ->executeOne();

        $engine = (new PhabricatorHomeProfileMenuEngine())
            ->setProfileObject($home_app)
            ->setCustomPHID($viewer->getPHID())
            ->setAction($this)
            ->setShowContentCrumbs(false);

        if (!$is_content) {
            $engine->addContentPageClass('phabricator-home');
        }

        return $engine->buildResponse();
    }

}
