<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\herald\systemaction\HeraldAction;
use orangins\modules\herald\systemaction\HeraldUtilityActionGroup;

/**
 * Class PhabricatorMailMustEncryptHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMailMustEncryptHeraldAction
    extends HeraldAction
{

    /**
     *
     */
    const DO_MUST_ENCRYPT = 'do.must-encrypt';

    /**
     *
     */
    const ACTIONCONST = 'email.must-encrypt';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Require secure email');
    }

    /**
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht(
            'Require mail content be transmitted only over secure channels.');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return PhabricatorMetaMTAEmailHeraldAction::isMailGeneratingObject($object);
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return HeraldUtilityActionGroup::ACTIONGROUPKEY;
    }

    /**
     * @param $rule_type
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return ($rule_type != HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
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
     * @param $object
     * @param HeraldEffect $effect
     * @return mixed|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function applyEffect($object, HeraldEffect $effect)
    {
        $rule_phid = $effect->getRule()->getPHID();

        $adapter = $this->getAdapter();
        $adapter->addMustEncryptReason($rule_phid);

        $this->logEffect(self::DO_MUST_ENCRYPT, array($rule_phid));
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_MUST_ENCRYPT => array(
                'icon' => 'fa-shield',
                'color' => 'blue',
                'name' => pht('Must Encrypt'),
            ),
        );
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        switch ($type) {
            case self::DO_MUST_ENCRYPT:
                return pht(
                    'Made it a requirement that mail content be transmitted only ' .
                    'over secure channels.');
        }
    }

}
