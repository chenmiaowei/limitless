<?php

namespace orangins\lib\response;

/**
 * Class AphrontHTMLResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
abstract class AphrontHTMLResponse extends AphrontResponse
{

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $headers = array(
            array('Content-Type', 'text/html; charset=UTF-8'),
        );
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }
}
