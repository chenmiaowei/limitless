<?php

namespace orangins\modules\auth\handler;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use orangins\lib\request\AphrontRequest;

/**
 * Class PhabricatorAuthLoginHandler
 * @package orangins\modules\auth\handler
 * @author 陈妙威
 */
abstract class PhabricatorAuthLoginHandler extends OranginsObject
{

    /**
     * @var AphrontRequest
     */
    private $request;
    /**
     * @var PhabricatorAction
     */
    private $delegatingAction;

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAuthLoginHeaderContent()
    {
        return array();
    }

    /**
     * @param PhabricatorAction $action
     * @return $this
     * @author 陈妙威
     */
    final public function setDelegatingAction(PhabricatorAction $action)
    {
        $this->delegatingAction = $action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getDelegatingAction()
    {
        return $this->delegatingAction;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    final public function setRequest(AphrontRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return AphrontRequest
     * @author 陈妙威
     */
    final public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return static[]
     * @author 陈妙威
     */
    final public static function getAllHandlers()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorAuthLoginHandler::class)
            ->execute();
    }
}
