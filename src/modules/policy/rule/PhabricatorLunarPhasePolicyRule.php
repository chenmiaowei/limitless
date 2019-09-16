<?php

namespace orangins\modules\policy\rule;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use PhutilLunarPhase;

/**
 * Class PhabricatorLunarPhasePolicyRule
 * @package orangins\modules\policy\rule
 * @author 陈妙威
 */
final class PhabricatorLunarPhasePolicyRule extends PhabricatorPolicyRule
{

    /**
     *
     */
    const PHASE_FULL = 'full';
    /**
     *
     */
    const PHASE_NEW = 'new';
    /**
     *
     */
    const PHASE_WAXING = 'waxing';
    /**
     *
     */
    const PHASE_WANING = 'waning';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRuleDescription()
    {
        return \Yii::t("app",'when the moon');
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $value
     * @param PhabricatorPolicyInterface $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function applyRule(
        PhabricatorUser $viewer,
        $value,
        PhabricatorPolicyInterface $object)
    {

        $moon = new PhutilLunarPhase(PhabricatorTime::getNow());

        switch ($value) {
            case 'full':
                return $moon->isFull();
            case 'new':
                return $moon->isNew();
            case 'waxing':
                return $moon->isWaxing();
            case 'waning':
                return $moon->isWaning();
            default:
                return false;
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getValueControlType()
    {
        return self::CONTROL_TYPE_SELECT;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    public function getValueControlTemplate()
    {
        return array(
            'options' => array(
                self::PHASE_FULL => \Yii::t("app",'is full'),
                self::PHASE_NEW => \Yii::t("app",'is new'),
                self::PHASE_WAXING => \Yii::t("app",'is waxing'),
                self::PHASE_WANING => \Yii::t("app",'is waning'),
            ),
        );
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getRuleOrder()
    {
        return 1000;
    }

}
