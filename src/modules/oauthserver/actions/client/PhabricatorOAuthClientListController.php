<?php

namespace orangins\modules\oauthserver\actions\client;

use orangins\modules\oauthserver\editor\PhabricatorOAuthServerEditEngine;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientSearchEngine;

/**
 * Class PhabricatorOAuthClientListController
 * @package orangins\modules\oauthserver\actions\client
 * @author 陈妙威
 */
final class PhabricatorOAuthClientListController
    extends PhabricatorOAuthClientController
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
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new  PhabricatorOAuthServerClientSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        (new  PhabricatorOAuthServerEditEngine())
            ->setViewer($this->getViewer())
            ->addActionToCrumbs($crumbs);

        return $crumbs;
    }

}
