<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use PhutilURI;
use yii\helpers\Url;

/**
 * Class PhabricatorDaemonController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
abstract class PhabricatorDaemonController
    extends PhabricatorAction
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
     * @return null|\orangins\lib\view\phui\PHUIListView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView(true)->getMenu();
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildSideNavView()
    {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $nav->addLabel(\Yii::t("app",'Daemons'));
        $nav->addFilter('/', \Yii::t("app",'Console'), Url::to(['/daemon/index/index']), "fa-calculator");
        $nav->addFilter('log', \Yii::t("app",'All Daemons'), Url::to(['/daemon/log/index']), "fa-coffee");

        $nav->addLabel(\Yii::t("app",'Bulk Jobs'));
        $nav->addFilter('bulk', \Yii::t("app",'Manage Bulk Jobs'), Url::to(['/daemon/bulk/query']), "fa-cube");

        return $nav;
    }

}
