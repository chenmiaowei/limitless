<?php

namespace orangins\modules\meta\actions;

use orangins\modules\meta\query\PhabricatorAppSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

/**
 * Class PhabricatorApplicationsListAction
 * @package orangins\modules\meta\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationsListAction extends PhabricatorApplicationsAction
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
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $action = (new PhabricatorApplicationSearchAction('search', $this->getController()))
            ->setQueryKey($this->getRequest()->getURIData('queryKey'))
            ->setSearchEngine(new PhabricatorAppSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($action);
    }

}
