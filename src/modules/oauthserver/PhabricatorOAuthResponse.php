<?php

namespace orangins\modules\oauthserver;

use orangins\lib\response\AphrontResponse;
use PhutilURI;

/**
 * Class PhabricatorOAuthResponse
 * @package orangins\modules\oauthserver
 * @author 陈妙威
 */
final class PhabricatorOAuthResponse extends AphrontResponse
{

    /**
     * @var
     */
    private $state;
    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $clientURI;
    /**
     * @var
     */
    private $error;
    /**
     * @var
     */
    private $errorDescription;

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getState()
    {
        return $this->state;
    }

    /**
     * @param $state
     * @return $this
     * @author 陈妙威
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getContent()
    {
        return $this->content;
    }

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
     * @return mixed
     * @author 陈妙威
     */
    private function getClientURI()
    {
        return $this->clientURI;
    }

    /**
     * @param PhutilURI $uri
     * @return $this
     * @author 陈妙威
     */
    public function setClientURI(PhutilURI $uri)
    {
        $this->setHTTPResponseCode(302);
        $this->clientURI = $uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFullURI()
    {
        $base_uri = $this->getClientURI();
        $query_params = $this->buildResponseDict();
        foreach ($query_params as $key => $value) {
            $base_uri->replaceQueryParam($key, $value);
        }
        return $base_uri;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getError()
    {
        return $this->error;
    }

    /**
     * @param $error
     * @return $this
     * @author 陈妙威
     */
    public function setError($error)
    {
        // errors sometimes redirect to the client (302) but otherwise
        // the spec says all code 400
        if (!$this->getClientURI()) {
            $this->setHTTPResponseCode(400);
        }
        $this->error = $error;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @param $error_description
     * @return $this
     * @author 陈妙威
     */
    public function setErrorDescription($error_description)
    {
        $this->errorDescription = $error_description;
        return $this;
    }

    /**
     * PhabricatorOAuthResponse constructor.
     */
    public function __construct()
    {
        $this->setHTTPResponseCode(200); // assume the best
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
        if ($this->getClientURI()) {
            $headers[] = array('Location', $this->getFullURI());
        }
        // TODO -- T844 set headers with X-Auth-Scopes, etc
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

    /**
     * @return array|mixed|string
     * @author 陈妙威
     */
    private function buildResponseDict()
    {
        if ($this->getError()) {
            $content = array(
                'error' => $this->getError(),
                'error_description' => $this->getErrorDescription(),
            );
            $this->setContent($content);
        }

        $content = $this->getContent();
        if (!$content) {
            return '';
        }
        if ($this->getState()) {
            $content['state'] = $this->getState();
        }
        return $content;
    }

    /**
     * @return mixed|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        return $this->encodeJSONForHTTPResponse($this->buildResponseDict());
    }

}
