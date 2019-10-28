<?php

namespace orangins\modules\metamta\herald;

use Exception;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\metamta\constants\PhabricatorMailRoutingRule;

/**
 * Class PhabricatorMailOutboundRoutingSelfEmailHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMailOutboundRoutingSelfEmailHeraldAction
    extends PhabricatorMailOutboundRoutingHeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'routing.self.email';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Deliver as email');
    }

    /**
     * @param $rule_type
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return ($rule_type == HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    }

    /**
     * @param $object
     * @param HeraldEffect $effect
     * @return mixed|void
     * @throws Exception
     * @author 陈妙威
     */
    public function applyEffect($object, HeraldEffect $effect)
    {
        $rule = $effect->getRule();
        $author_phid = $rule->getAuthorPHID();

        $this->applyRouting(
            $rule,
            PhabricatorMailRoutingRule::ROUTE_AS_MAIL,
            array($author_phid));
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
     * @return mixed|string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht('Deliver as email.');
    }

}
