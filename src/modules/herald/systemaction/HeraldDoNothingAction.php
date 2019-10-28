<?php

namespace orangins\modules\herald\systemaction;

use orangins\modules\herald\engine\HeraldEffect;

/**
 * Class HeraldDoNothingAction
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldDoNothingAction extends HeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'nothing';
    /**
     *
     */
    const DO_NOTHING = 'do.nothing';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Do nothing');
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return HeraldUtilityActionGroup::ACTIONGROUPKEY;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return true;
    }

    /**
     * @param $rule_type
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return true;
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
        $this->logEffect(self::DO_NOTHING);
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
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_NOTHING => array(
                'icon' => 'fa-check',
                'color' => 'grey',
                'name' => pht('Did Nothing'),
            ),
        );
    }

    /**
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht('Do nothing.');
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        return pht('Did nothing.');
    }

}
