<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/7
 * Time: 11:48 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\actions;


use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use orangins\modules\userservice\query\PhabricatorUserServiceSearchEngine;
use PhutilURI;

/**
 * Class PhabricatorUserServiceListAction
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
class PhabricatorUserServiceListAction extends PhabricatorUserServiceAction
{
    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');

        $action = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine(
                (new PhabricatorUserServiceSearchEngine())->setAction($this))
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($action);
    }


    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addAction(
            (new PHUIListItemView())
                ->setName(\Yii::t("app",'创建用户服务'))
                ->setWorkflow(true)
                ->setIcon('fa-upload')
                ->setHref($this->getApplicationURI('index/create')));

        return $crumbs;
    }
}