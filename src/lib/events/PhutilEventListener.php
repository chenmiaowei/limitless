<?php

namespace orangins\lib\events;

use orangins\lib\OranginsObject;
use yii\base\Event;

/**
 * Class PhutilEventListener
 * @package orangins\lib\events
 * @author 陈妙威
 */
abstract class PhutilEventListener extends OranginsObject
{
    /**
     * @var
     */
    private $listenerID;
    /**
     * @var int
     */
    private static $nextListenerID = 1;


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function register();

    /**
     * @param Event $event
     * @return mixed
     * @author 陈妙威
     */
    abstract public function handleEvent(Event $event);

    /**
     * @param $type
     * @author 陈妙威
     */
    final public function listen($type)
    {
        \Yii::$app->on($type, [$this, 'handleEvent']);
    }


    /**
     * Return a scalar ID unique to this listener. This is used to deduplicate
     * listeners which match events on multiple rules, so they are invoked only
     * once.
     *
     * @return int A scalar unique to this object instance.
     */
    final public function getListenerID()
    {
        if (!$this->listenerID) {
            $this->listenerID = self::$nextListenerID;
            self::$nextListenerID++;
        }
        return $this->listenerID;
    }


}
