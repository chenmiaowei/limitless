<?php

namespace orangins\modules\oauthserver\actions\client;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\oauthserver\actions\PhabricatorOAuthServerController;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientSearchEngine;
use PhutilURI;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorOAuthClientController
 * @package orangins\modules\oauthserver\actions\client
 * @author 陈妙威
 */
abstract class PhabricatorOAuthClientController extends PhabricatorOAuthServerController
{

    /**
     * @var
     */
    private $clientPHID;

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getClientPHID()
    {
        return $this->clientPHID;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    private function setClientPHID($phid)
    {
        $this->clientPHID = $phid;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return true;
    }

    /**
     * @param array $data
     * @author 陈妙威
     */
    public function willProcessRequest(array $data)
    {
        $this->setClientPHID(ArrayHelper::getValue($data, 'phid'));
    }

    /**
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView($for_app = false)
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new  PhabricatorOAuthServerClientSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

}
