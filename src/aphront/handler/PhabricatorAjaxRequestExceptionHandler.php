<?php

namespace orangins\aphront\handler;

use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontAjaxResponse;

/**
 * Class PhabricatorAjaxRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
final class PhabricatorAjaxRequestExceptionHandler
    extends PhabricatorRequestExceptionHandler
{

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerPriority()
    {
        return 110000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerDescription()
    {
        return pht('Responds to requests made by AJAX clients.');
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
        // For non-workflow requests, return a Ajax response.
        return ($request->isAjax() && !$request->isWorkflow());
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed|AphrontAjaxResponse
     * @author 陈妙威
     */
    public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        // Log these; they don't get shown on the client and can be difficult
        // to debug.
        phlog($throwable);

        $response = new AphrontAjaxResponse();
        $response->setError(
            array(
                'code' => get_class($throwable),
                'info' => $throwable->getMessage(),
            ));

        return $response;
    }

}
