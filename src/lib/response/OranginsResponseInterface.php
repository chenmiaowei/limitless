<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/7
 * Time: 3:20 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\response;

/**
 * Interface OranginsResponseInterface
 * @package orangins\lib\response
 */
interface OranginsResponseInterface
{
    /**
     * 处理客户端请求，并返回结果
     * @return AphrontResponse|string
     * @author 陈妙威
     */
    public function buildResponse();
}