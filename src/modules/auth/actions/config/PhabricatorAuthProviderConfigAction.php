<?php

namespace orangins\modules\auth\actions\config;

use orangins\modules\auth\actions\PhabricatorAuthAction;
use orangins\modules\auth\capability\AuthManageProvidersCapability;
use PhutilURI;
use orangins\lib\view\layout\AphrontSideNavFilterView;

/**
 * Class PhabricatorAuthProviderConfigController
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
abstract class PhabricatorAuthProviderConfigAction extends PhabricatorAuthAction
{

    /**
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildSideNavView($for_app = false)
    {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        if ($for_app) {
            $nav->addLabel(\Yii::t("app", 'Create'));
            $nav->addFilter('',
                \Yii::t("app", 'Add Authentication Provider'),
                $this->getApplicationURI('config/new'));
        }
        return $nav;
    }

    /**
     * @return null
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView($for_app = true)->getMenu();
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $can_create = $this->hasApplicationCapability(AuthManageProvidersCapability::CAPABILITY);
        return $crumbs;
    }

}
