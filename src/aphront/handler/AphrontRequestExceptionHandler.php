<?php

namespace orangins\aphront\handler;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use PhutilClassMapQuery;

/**
 * React to an unhandled exception escaping request handling in a controller
 * and convert it into a response.
 *
 * These handlers are generally used to render error pages, but they may
 * also perform more specialized handling in situations where an error page
 * is not appropriate.
 */
abstract class AphrontRequestExceptionHandler extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getRequestExceptionHandlerPriority();

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed
     * @author 陈妙威
     */
    abstract public function canHandleRequestThrowable(
        AphrontRequest $request,
        $throwable);

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed
     * @author 陈妙威
     */
    abstract public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable);

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllHandlers()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setSortMethod('getRequestExceptionHandlerPriority')
            ->execute();
    }

}
