<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 12:07 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\response;


class AphrontPureHTMLResponse extends AphrontHTMLResponse
{
    /**
     * @var
     */
    private $content;

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        return $this->content;
    }
}