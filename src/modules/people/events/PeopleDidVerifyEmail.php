<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/24
 * Time: 4:16 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\events;

use orangins\lib\events\Event;
use orangins\lib\view\layout\PhabricatorActionView;

/**
 * Class RenderActionListEvent
 * @package orangins\lib\events
 * @author 陈妙威
 */
class PeopleDidVerifyEmail extends Event
{
    /**
     * @var
     */
    private $email;

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
}