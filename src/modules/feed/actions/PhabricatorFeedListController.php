<?php

namespace orangins\modules\feed\actions;

use orangins\modules\feed\query\PhabricatorFeedSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

/**
 * Class PhabricatorFeedListController
 * @package orangins\modules\feed\actions
 * @author 陈妙威
 */
final class PhabricatorFeedListController extends PhabricatorFeedController
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
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');

        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine(new PhabricatorFeedSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($controller);
    }
}
