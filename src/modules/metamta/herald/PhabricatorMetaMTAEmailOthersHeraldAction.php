<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableDatasource;

/**
 * Class PhabricatorMetaMTAEmailOthersHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMetaMTAEmailOthersHeraldAction
    extends PhabricatorMetaMTAEmailHeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'email.other';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Send an email to');
    }

    /**
     * @param $rule_type
     * @return bool
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return ($rule_type != HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    }

    /**
     * @param $object
     * @param HeraldEffect $effect
     * @author 陈妙威
     */
    public function applyEffect($object, HeraldEffect $effect)
    {
        return $this->applyEmail($effect->getTarget(), $force = false);
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        return self::STANDARD_PHID_LIST;
    }

    /**
     * @return PhabricatorMetaMTAMailableDatasource|void
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new PhabricatorMetaMTAMailableDatasource();
    }

    /**
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht('Send an email to: %s.', $this->renderHandleList($value));
    }

}
