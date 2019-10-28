<?php

namespace orangins\modules\herald\engine;

use orangins\modules\herald\actions\HeraldRuleAction;
use orangins\modules\herald\models\HeraldRule;
use Phobject;

/**
 * Class HeraldEffect
 * @package orangins\modules\herald\engine
 * @author 陈妙威
 */
final class HeraldEffect extends Phobject
{

    /**
     * @var
     */
    private $objectPHID;
    /**
     * @var HeraldRuleAction
     */
    private $action;
    /**
     * @var
     */
    private $target;
    /**
     * @var HeraldRule
     */
    private $rule;
    /**
     * @var
     */
    private $reason;

    /**
     * @param $object_phid
     * @return $this
     * @author 陈妙威
     */
    public function setObjectPHID($object_phid)
    {
        $this->objectPHID = $object_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObjectPHID()
    {
        return $this->objectPHID;
    }

    /**
     * @param $action
     * @return $this
     * @author 陈妙威
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $target
     * @return $this
     * @author 陈妙威
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param HeraldRule $rule
     * @return $this
     * @author 陈妙威
     */
    public function setRule(HeraldRule $rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @return HeraldRule
     * @author 陈妙威
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param $reason
     * @return $this
     * @author 陈妙威
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReason()
    {
        return $this->reason;
    }

}
