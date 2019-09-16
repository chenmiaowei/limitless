<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/29
 * Time: 10:01 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\actions;


use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\rbac\query\PhabricatorRBACRoleSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

class PhabricatorRBACRoleListAction extends PhabricatorRBACAction
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
                (new PhabricatorRBACRoleSearchEngine()));

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
                ->setName(\Yii::t("app",'创建角色'))
                ->setIcon('fa-upload')
                ->setHref($this->getApplicationURI('role/create')));

        return $crumbs;
    }
}