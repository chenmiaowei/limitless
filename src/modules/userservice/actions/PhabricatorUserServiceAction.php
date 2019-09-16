<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 11:43 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\actions;


use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\userservice\query\PhabricatorUserServiceSearchEngine;
use PhutilURI;

/**
 * Class PhabricatorUserServiceAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceAction extends PhabricatorAction
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


        (new PhabricatorUserServiceSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        return $nav;
    }
}