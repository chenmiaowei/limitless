<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 12:04 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\response;

class AphrontPureJSONResponse  extends AphrontResponse
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
        return json_encode($this->content);
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $headers = array(
            array('Content-Type', 'application/json'),
        );
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

}
