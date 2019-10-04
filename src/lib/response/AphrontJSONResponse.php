<?php

namespace orangins\lib\response;

/**
 * Class AphrontJSONResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontJSONResponse extends AphrontResponse
{

    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $addJSONShield;

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
     * @param $should_add
     * @return $this
     * @author 陈妙威
     */
    public function setAddJSONShield($should_add)
    {
        $this->addJSONShield = $should_add;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAddJSONShield()
    {
        if ($this->addJSONShield === null) {
            return true;
        }
        return (bool)$this->addJSONShield;
    }

    /**
     * @return mixed|string|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        $response = $this->encodeJSONForHTTPResponse($this->content);
        if ($this->shouldAddJSONShield()) {
            $response = $this->addJSONShield($response);
        }
        return $response;
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
            array('Origin', '*'),
            array('Access-Control-Allow-Origin', 'http://localhost:4200'),
            array('Access-Control-Request-Method', 'GET,POST'),
            array('Access-Control-Allow-Credentials', 'true'),
            array('Access-Control-Max-Age', '3600'),
            array('Access-Control-Allow-Headerse', 'Content-Type,Access-Token'),
        );
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

}
