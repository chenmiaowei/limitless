<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/24
 * Time: 4:16 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\events;

use orangins\lib\events\Event;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\layout\PhabricatorActionView;

/**
 * Class RenderActionListEvent
 * @package orangins\lib\events
 * @author 陈妙威
 */
class AuthWillLoginUserEvent extends Event
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var AphrontResponse
     */
    private $response;

    /**
     * @var bool
     */
    private $shouldLogin;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return AphrontResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @return self
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getShouldLogin()
    {
        return $this->shouldLogin;
    }

    /**
     * @param mixed $shouldLogin
     * @return self
     */
    public function setShouldLogin($shouldLogin)
    {
        $this->shouldLogin = $shouldLogin;
        return $this;
    }
}