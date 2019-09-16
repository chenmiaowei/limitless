<?php

namespace orangins\lib\response;

use Exception;

/**
 * Base class for responses which augment other types of responses. For example,
 * a response might be substantially an Ajax response, but add structure to the
 * response content. It can do this by extending @{class:AphrontProxyResponse},
 * instantiating an @{class:AphrontAjaxResponse} in @{method:buildProxy}, and
 * then constructing a real @{class:AphrontAjaxResponse} in
 * @{method:reduceProxyResponse}.
 */
abstract class AphrontProxyResponse
    extends AphrontResponse
    implements AphrontResponseProducerInterface
{

    /**
     * @var
     */
    private $proxy;

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getProxy()
    {
        if (!$this->proxy) {
            $this->proxy = $this->buildProxy();
        }
        return $this->proxy;
    }

    /**
     * @param \yii\web\Request $request
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setRequest($request)
    {
        $this->getProxy()->setRequest($request);
        return $this;
    }

    /**
     * @return mixed|\yii\web\Request
     * @author 陈妙威
     */
    public function getRequest()
    {
        return $this->getProxy()->getRequest();
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getHeaders()
    {
        return $this->getProxy()->getHeaders();
    }

    /**
     * @param $duration
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setCacheDurationInSeconds($duration)
    {
        $this->getProxy()->setCacheDurationInSeconds($duration);
        return $this;
    }

    /**
     * @param $can_cdn
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setCanCDN($can_cdn)
    {
        $this->getProxy()->setCanCDN($can_cdn);
        return $this;
    }

    /**
     * @param $epoch_timestamp
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setLastModified($epoch_timestamp)
    {
        $this->getProxy()->setLastModified($epoch_timestamp);
        return $this;
    }

    /**
     * @param $code
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setHTTPResponseCode($code)
    {
        $this->getProxy()->setHTTPResponseCode($code);
        return $this;
    }

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return $this->getProxy()->getHTTPResponseCode();
    }

    /**
     * @param $frameable
     * @return $this|AphrontResponse
     * @author 陈妙威
     */
    public function setFrameable($frameable)
    {
        $this->getProxy()->setFrameable($frameable);
        return $this;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getCacheHeaders()
    {
        return $this->getProxy()->getCacheHeaders();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function buildProxy();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function reduceProxyResponse();

    /**
     * @author 陈妙威
     * @throws Exception
     */
    final public function buildResponseString()
    {
        throw new Exception(
            \Yii::t("app",
                '{0} must implement {1}.',
                [
                    __CLASS__,
                    'reduceProxyResponse()'
                ]));
    }


    /* -(  AphrontResponseProducerInterface  )----------------------------------- */


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function produceAphrontResponse()
    {
        return $this->reduceProxyResponse();
    }

}
