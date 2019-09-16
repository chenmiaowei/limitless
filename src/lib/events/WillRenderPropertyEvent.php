<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/28
 * Time: 3:42 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\events;

/**
 * Class WillRenderPropertyEvent
 * @package orangins\lib\events
 * @author 陈妙威
 */
class WillRenderPropertyEvent extends Event
{
    /**
     * @var
     */
    public $object;
    /**
     * @var
     */
    public $view;

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
     * @return mixed
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param mixed $view
     * @return self
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }
}