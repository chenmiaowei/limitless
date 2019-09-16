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
     * @throws \yii\base\Exception
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
     * @throws \yii\base\Exception
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
