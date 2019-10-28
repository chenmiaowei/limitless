<?php

namespace orangins\modules\herald\actions;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\herald\query\HeraldRuleSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

/**
 * Class HeraldRuleListController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldRuleListController extends HeraldController
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
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');

        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine(new HeraldRuleSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($controller);
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
                ->setName(pht('Create Herald Rule'))
                ->setHref($this->getApplicationURI('index/create'))
                ->setIcon('fa-plus-square'));

        return $crumbs;
    }

}
