<?php

namespace orangins\lib\response;

/**
 * Class AphrontPlainTextResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontPlainTextResponse extends AphrontResponse
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
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        return $this->content;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $headers = array(
            array('Content-Type', 'text/plain; charset=utf-8'),
        );

        return array_merge(parent::getHeaders(), $headers);
    }
}
