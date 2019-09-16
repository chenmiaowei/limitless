<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/14
 * Time: 11:11 AM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\finance\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use app\task\query\PhabricatorTaskSearchEngine;
use PhutilURI;

/**
 * Class FinanceAction
 * @package orangins\modules\finance\actions
 * @author 陈妙威
 */
class FinanceAction extends PhabricatorAction
{
    /**
     * @return null
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView(true)->getMenu();
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
        $viewer = $this->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $nav->addFilter('dashboard', \Yii::t("app", "Dashboard"), $this->getApplicationURI("index/dashboard"), "fa-dashboard");

        $nav->addLabel("Bill");
        $nav->addFilter('publish-type', \Yii::t("app", "Task Publish"), $this->getApplicationURI("index/edit"), "fa-pencil");

        return $nav;
    }
}