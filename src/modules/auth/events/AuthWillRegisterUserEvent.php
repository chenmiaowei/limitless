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
use orangins\lib\view\layout\PhabricatorActionView;

/**
 * Class RenderActionListEvent
 * @package orangins\lib\events
 * @author 陈妙威
 */
class AuthWillRegisterUserEvent extends Event
{
    /**
     * @var
     */
    private $account;

    /**
     * @var
     */
    private $profile;

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param mixed $account
     * @return self
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param mixed $profile
     * @return self
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
        return $this;
    }
}