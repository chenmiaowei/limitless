<?php

namespace orangins\aphront\handler;

use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontJSONResponse;
use orangins\modules\conduit\protocol\ConduitAPIResponse;

/**
 * Class PhabricatorConduitRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
final class PhabricatorConduitRequestExceptionHandler
    extends PhabricatorRequestExceptionHandler
{

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerPriority()
    {
        return 100000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerDescription()
    {
        return pht('Responds to requests made by Conduit clients.');
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return bool|mixed
     * @author 陈妙威
     */
    public function canHandleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {
        return $request->isConduit();
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed
     * @author 陈妙威
     */
    public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        $response = (new ConduitAPIResponse())
            ->setErrorCode(get_class($throwable))
            ->setErrorInfo($throwable->getMessage());

        return id(new AphrontJSONResponse())
            ->setAddJSONShield(false)
            ->setContent($response->toDictionary());
    }
}
