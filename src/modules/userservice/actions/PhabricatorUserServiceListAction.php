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
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');
        $nav = $this->newNavigation($this->getViewer());

        $action = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine(
                (new PhabricatorUserServiceSearchEngine())->setAction($this))
            ->setNavigation($nav);

        $delegateToAction = $this->delegateToAction($action);
        $nav->selectFilter("userservice-index");
        return $delegateToAction;
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
                ->setIcon('fa-plus')
                ->setHref($this->getApplicationURI('index/create')));

        return $crumbs;
    }
}