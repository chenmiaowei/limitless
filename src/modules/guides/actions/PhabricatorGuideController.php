<?php

namespace orangins\modules\guides\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use PhabricatorGuideModule;

/**
 * Class PhabricatorGuideController
 * @package orangins\modules\guides\actions
 * @author 陈妙威
 */
abstract class PhabricatorGuideController extends PhabricatorAction
{

    /**
     * @param null $filter
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSideNavView($filter = null, $for_app = false)
    {

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));
        $nav->addLabel(\Yii::t("app",'Guides'));

        $modules = PhabricatorGuideModule::getEnabledModules();
        foreach ($modules as $key => $module) {
            $nav->addFilter($key . '/', $module->getModuleName());
        }

        return $nav;
    }

    /**
     * @return null
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView(null, true)->getMenu();
    }

}
