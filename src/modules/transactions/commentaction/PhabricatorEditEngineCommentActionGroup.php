<?php

namespace orangins\modules\transactions\commentaction;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorEditEngineCommentActionGroup
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineCommentActionGroup extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $label;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLabel()
    {
        return $this->label;
    }

}
