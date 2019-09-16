<?php

namespace orangins\modules\conduit\actions;

use orangins\modules\conduit\query\PhabricatorConduitSearchEngine;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;

/**
 * Class PhabricatorConduitListController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitListController
    extends PhabricatorConduitController
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
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $controller = (new PhabricatorApplicationSearchAction("search", $this->controller))
            ->setQueryKey($request->getURIData('queryKey'))
            ->setSearchEngine(new PhabricatorConduitSearchEngine())
            ->setNavigation($this->buildSideNavView());
        return $this->delegateToAction($controller);
    }

}
