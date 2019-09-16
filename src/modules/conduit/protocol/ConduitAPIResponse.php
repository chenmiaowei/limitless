<?php

namespace orangins\modules\conduit\protocol;

use orangins\lib\OranginsObject;

/**
 * Class ConduitAPIResponse
 * @package orangins\modules\conduit\protocol
 * @author 陈妙威
 */
final class ConduitAPIResponse extends OranginsObject
{

    /**
     * @var
     */
    private $result;
    /**
     * @var
     */
    private $errorCode;
    /**
     * @var
     */
    private $errorInfo;

    /**
     * @param $result
     * @return $this
     * @author 陈妙威
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $error_code
     * @return $this
     * @author 陈妙威
     */
    public function setErrorCode($error_code)
    {
        $this->errorCode = $error_code;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param $error_info
     * @return $this
     * @author 陈妙威
     */
    public function setErrorInfo($error_info)
    {
        $this->errorInfo = $error_info;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getErrorInfo()
    {
        return $this->errorInfo;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        return array(
            'result' => $this->getResult(),
            'error_code' => $this->getErrorCode(),
            'error_info' => $this->getErrorInfo(),
        );
    }

}
