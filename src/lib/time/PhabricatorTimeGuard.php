<?php

namespace orangins\lib\time;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorTimeGuard
 * @package orangins\lib\time
 * @author 陈妙威
 */
final class PhabricatorTimeGuard extends OranginsObject
{

    /**
     * @var
     */
    private $frameKey;

    /**
     * PhabricatorTimeGuard constructor.
     * @param $frame_key
     */
    public function __construct($frame_key)
    {
        $this->frameKey = $frame_key;
    }


    /**
     * @throws \yii\base\Exception
     */
    public function __destruct()
    {
        PhabricatorTime::popTime($this->frameKey);
    }
}
