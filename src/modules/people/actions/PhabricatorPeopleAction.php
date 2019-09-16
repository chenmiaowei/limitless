<?php

namespace orangins\modules\people\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\auth\provider\PhabricatorLDAPAuthProvider;
use orangins\modules\people\query\PhabricatorPeopleSearchEngine;
use PhutilURI;

/**
 * Class PhabricatorPeopleController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
abstract class PhabricatorPeopleAction extends PhabricatorAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return true;
    }

    /**
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView($for_app = false)
    {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $name = null;
        if ($for_app) {
            $name = $this->getRequest()->getURIData('username');
            if ($name) {
                $nav->setBaseURI(new PhutilURI('/p/'));
                $nav->addFilter("{$name}/", $name);
                $nav->addFilter("{$name}/calendar/", \Yii::t("app",'Calendar'));
            }
        }

        if (!$name) {
            $viewer = $this->getRequest()->getViewer();
            (new PhabricatorPeopleSearchEngine())
                ->setViewer($viewer)
                ->addNavigationItems($nav->getMenu());

            if ($viewer->getIsAdmin()) {
                $nav->addLabel(\Yii::t("app",'User Administration'));
                if (PhabricatorLDAPAuthProvider::getLDAPProvider()) {
                    $nav->addFilter('ldap', \Yii::t("app",'Import from LDAP'));
                }

                $nav->addFilter('logs', \Yii::t("app",'Activity Logs'), $this->getApplicationURI("index/logs"));
                $nav->addFilter('invite', \Yii::t("app",'Email Invitations'), $this->getApplicationURI("index/logs"));
            }
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
        return $this->buildSideNavView(true)->getMenu();
    }

}
