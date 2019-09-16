<?php

namespace orangins\modules\celerity;

use orangins\lib\OranginsObject;

/**
 * Indirection layer which provisions for a terrifying future where we need to
 * build multiple resource responses per page.
 */
final class CelerityAPI extends OranginsObject
{

    /**
     * @var
     */
    private static $response;

    /**
     * @return CelerityStaticResourceResponse
     * @author 陈妙威
     */
    public static function getStaticResourceResponse()
    {
        if (empty(self::$response)) {
            self::$response = new CelerityStaticResourceResponse();
        }
        return self::$response;
    }
}
