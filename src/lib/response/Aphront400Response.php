<?php

namespace orangins\lib\response;

/**
 * 400 Bad Request 由于明显的客户端错误（例如，格式错误的请求语法，太大的大小，无效的请求消息或欺骗性路由请求），服务器不能或不会处理该请求
 * Class Aphront400Response
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class Aphront400Response extends AphrontResponse
{

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 400;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        return '400 Bad Request';
    }
}
