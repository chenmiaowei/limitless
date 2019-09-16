<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/24
 * Time: 4:16 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\events;

use orangins\lib\view\layout\PhabricatorActionView;

/**
 * Class RenderActionListEvent
 * @package orangins\lib\events
 * @author 陈妙威
 */
class RenderActionListEvent extends Event
{
    /**
     * @var
     */
    private $object;

    /**
     * @var
     */
    private $actions;


    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $object
     * @return self
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return PhabricatorActionView[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param mixed $actions
     * @return self
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
        return $this;
    }
}