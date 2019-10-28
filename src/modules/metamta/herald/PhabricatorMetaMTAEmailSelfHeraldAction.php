<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;

/**
 * Class PhabricatorMetaMTAEmailSelfHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMetaMTAEmailSelfHeraldAction
    extends PhabricatorMetaMTAEmailHeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'email.self';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Send me an email');
    }

    /**
     * @param $rule_type
     * @return bool
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return ($rule_type == HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    }

    /**
     * @param $object
     * @param HeraldEffect $effect
     * @author 陈妙威
     */
    public function applyEffect($object, HeraldEffect $effect)
    {
        $phid = $effect->getRule()->getAuthorPHID();

        // For personal rules, we'll force delivery of a real email. This effect
        // is stronger than notification preferences, so you get an actual email
        // even if your preferences are set to "Notify" or "Ignore".

        return $this->applyEmail(array($phid), $force = true);
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        return self::STANDARD_NONE;
    }

    /**
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht('Send an email to rule author.');
    }

}
