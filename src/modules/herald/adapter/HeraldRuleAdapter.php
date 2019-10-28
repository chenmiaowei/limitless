<?php

namespace orangins\modules\herald\adapter;

use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\models\HeraldRule;

/**
 * Class HeraldRuleAdapter
 * @package orangins\modules\herald\adapter
 * @author 陈妙威
 */
final class HeraldRuleAdapter extends HeraldAdapter
{

    /**
     * @var
     */
    private $rule;

    /**
     * @return object|HeraldRule|null
     * @author 陈妙威
     */
    protected function newObject()
    {
        return new HeraldRule();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterApplicationClass()
    {
        return PhabricatorHeraldApplication::className();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterContentDescription()
    {
        return pht('React to Herald rules being created or updated.');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function isTestAdapterForObject($object)
    {
        return ($object instanceof HeraldRule);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getAdapterTestDescription()
    {
        return pht(
            'Test rules which run when another Herald rule is created or ' .
            'updated.');
    }

    /**
     * @return HeraldAdapter|void
     * @author 陈妙威
     */
    protected function initializeNewAdapter()
    {
        $this->rule = $this->newObject();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsApplicationEmail()
    {
        return true;
    }

    /**
     * @param $rule_type
     * @return bool
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        switch ($rule_type) {
            case HeraldRuleTypeConfig::RULE_TYPE_GLOBAL:
            case HeraldRuleTypeConfig::RULE_TYPE_PERSONAL:
                return true;
            case HeraldRuleTypeConfig::RULE_TYPE_OBJECT:
            default:
                return false;
        }
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
     * @return mixed
     * @author 陈妙威
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->rule = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->rule;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterContentName()
    {
        return pht('Herald Rules');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldName()
    {
        return $this->getRule()->getMonogram();
    }

}
