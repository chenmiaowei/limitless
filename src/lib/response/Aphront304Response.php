<?php

namespace orangins\lib\response;

/**
 * HTTP 303 See Other 进行URL重定向的操作
 * Class Aphront304Response
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class Aphront304Response extends AphrontResponse
{

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 304;
    }

    /**
     * @return null|void
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        // IMPORTANT! According to the HTTP/1.1 spec (RFC 2616) a 304 response
        // "MUST NOT" have any content. Apache + Safari strongly agree, and
        // completely flip out and you start getting 304s for no-cache pages.
        return null;
    }
}
