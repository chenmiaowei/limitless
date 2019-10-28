<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;

/**
 * Class PhabricatorMailOutboundMailHeraldAdapter
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMailOutboundMailHeraldAdapter
    extends HeraldAdapter
{

    /**
     * @var
     */
    private $mail;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterApplicationClass()
    {
        return PhabricatorMetaMTAApplication::className();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterContentDescription()
    {
        return pht('Route outbound email.');
    }

    /**
     * @return HeraldAdapter|void
     * @author 陈妙威
     */
    protected function initializeNewAdapter()
    {
        $this->mail = $this->newObject();
    }

    /**
     * @return object|PhabricatorMetaMTAMail|null
     * @author 陈妙威
     */
    protected function newObject()
    {
        return new PhabricatorMetaMTAMail();
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function isTestAdapterForObject($object)
    {
        return ($object instanceof PhabricatorMetaMTAMail);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getAdapterTestDescription()
    {
        return pht(
            'Test rules which run when outbound mail is being prepared for ' .
            'delivery.');
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->mail;
    }

    /**
     * @param PhabricatorMetaMTAMail $mail
     * @return $this
     * @author 陈妙威
     */
    public function setObject($mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAdapterContentName()
    {
        return pht('Outbound Mail');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isSingleEventAdapter()
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
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldName()
    {
        return pht('Mail %d', $this->getObject()->getID());
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsWebhooks()
    {
        return false;
    }

}
