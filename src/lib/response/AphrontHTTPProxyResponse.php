<?php

namespace orangins\lib\response;

use HTTPSFuture;

/**
 * Responds to a request by proxying an HTTP future.
 *
 * NOTE: This is currently very inefficient for large responses, and buffers
 * the entire response into memory before returning it. It should be updated
 * to stream the response instead, but we need to complete additional
 * infrastructure work first.
 */
final class AphrontHTTPProxyResponse extends AphrontResponse
{

    /**
     * @var
     */
    private $future;
    /**
     * @var
     */
    private $headers;
    /**
     * @var
     */
    private $httpCode;

    /**
     * @param HTTPSFuture $future
     * @return $this
     * @author 陈妙威
     */
    public function setHTTPFuture(HTTPSFuture $future)
    {
        $this->future = $future;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHTTPFuture()
    {
        return $this->future;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCacheHeaders()
    {
        return array();
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $this->readRequestHeaders();
        return array_merge(
            parent::getHeaders(),
            $this->headers,
            array(
                array('X-Phabricator-Proxy', 'true'),
            ));
    }

    /**
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        // TODO: AphrontResponse needs to support streaming responses.
        return $this->readRequest();
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        $this->readRequestHeaders();
        return $this->httpCode;
    }

    /**
     * @author 陈妙威
     */
    private function readRequestHeaders()
    {
        // TODO: This should read only the headers.
        $this->readRequest();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function readRequest()
    {
        // TODO: This is grossly inefficient for large requests.

        list($status, $body, $headers) = $this->future->resolve();
        $this->httpCode = $status->getStatusCode();

        // Strip "Transfer-Encoding" headers. Particularly, the server we proxied
        // may have chunked the response, but cURL will already have un-chunked it.
        // If we emit the header and unchunked data, the response becomes invalid.
        foreach ($headers as $key => $header) {
            list($header_head, $header_body) = $header;
            $header_head = phutil_utf8_strtolower($header_head);
            switch ($header_head) {
                case 'transfer-encoding':
                    unset($headers[$key]);
                    break;
            }
        }

        $this->headers = $headers;

        return $body;
    }

}
