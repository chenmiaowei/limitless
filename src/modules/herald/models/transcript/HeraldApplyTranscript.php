<?php

namespace orangins\modules\herald\models\transcript;

use orangins\modules\herald\engine\HeraldEffect;
use Phobject;

/**
 * Class HeraldApplyTranscript
 * @package orangins\modules\herald\models\transcript
 * @author 陈妙威
 */
final class HeraldApplyTranscript extends Phobject
{

    /**
     * @var
     */
    private $action;
    /**
     * @var
     */
    private $target;
    /**
     * @var
     */
    private $ruleID;
    /**
     * @var
     */
    private $reason;
    /**
     * @var
     */
    private $applied;
    /**
     * @var
     */
    private $appliedReason;

    /**
     * HeraldApplyTranscript constructor.
     * @param HeraldEffect $effect
     * @param $applied
     * @param null $reason
     */
    public function __construct(
        HeraldEffect $effect,
        $applied,
        $reason = null)
    {

        $this->setAction($effect->getAction());
        $this->setTarget($effect->getTarget());
        if ($effect->getRule()) {
            $this->setRuleID($effect->getRule()->getID());
        }
        $this->setReason($effect->getReason());
        $this->setApplied($applied);
        $this->setAppliedReason($reason);
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
     * @param $rule_id
     * @return $this
     * @author 陈妙威
     */
    public function setRuleID($rule_id)
    {
        $this->ruleID = $rule_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRuleID()
    {
        return $this->ruleID;
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

    /**
     * @param $applied
     * @return $this
     * @author 陈妙威
     */
    public function setApplied($applied)
    {
        $this->applied = $applied;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplied()
    {
        return $this->applied;
    }

    /**
     * @param $applied_reason
     * @return $this
     * @author 陈妙威
     */
    public function setAppliedReason($applied_reason)
    {
        $this->appliedReason = $applied_reason;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAppliedReason()
    {
        return $this->appliedReason;
    }

}
