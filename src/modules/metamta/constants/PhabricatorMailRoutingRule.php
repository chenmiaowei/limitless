<?php

namespace orangins\modules\metamta\constants;


use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMailRoutingRule
 * @package orangins\modules\metamta\constants
 * @author 陈妙威
 */
final class PhabricatorMailRoutingRule extends OranginsObject
{

    /**
     *
     */
    const ROUTE_AS_NOTIFICATION = 'route.notification';
    /**
     *
     */
    const ROUTE_AS_MAIL = 'route.mail';

    /**
     * @param $rule_u
     * @param $rule_v
     * @return bool
     * @author 陈妙威
     */
    public static function isStrongerThan($rule_u, $rule_v)
    {
        $strength_u = self::getRuleStrength($rule_u);
        $strength_v = self::getRuleStrength($rule_v);

        return ($strength_u > $strength_v);
    }

    /**
     * @param $const
     * @return mixed
     * @author 陈妙威
     */
    public static function getRuleStrength($const)
    {
        $strength = array(
            self::ROUTE_AS_NOTIFICATION => 1,
            self::ROUTE_AS_MAIL => 2,
        );

        return ArrayHelper::getValue($strength, $const, 0);
    }

    /**
     * @param $const
     * @return mixed
     * @author 陈妙威
     */
    public static function getRuleName($const)
    {
        $names = array(
            self::ROUTE_AS_NOTIFICATION => \Yii::t("app", 'Route as Notification'),
            self::ROUTE_AS_MAIL => \Yii::t("app", 'Route as Mail'),
        );

        return ArrayHelper::getValue($names, $const, $const);
    }

    /**
     * @param $const
     * @return mixed
     * @author 陈妙威
     */
    public static function getRuleIcon($const)
    {
        $icons = array(
            self::ROUTE_AS_NOTIFICATION => 'fa-bell',
            self::ROUTE_AS_MAIL => 'fa-envelope',
        );

        return ArrayHelper::getValue($icons, $const, 'fa-question-circle');
    }

    /**
     * @param $const
     * @return mixed
     * @author 陈妙威
     */
    public static function getRuleColor($const)
    {
        $colors = array(
            self::ROUTE_AS_NOTIFICATION => 'grey',
            self::ROUTE_AS_MAIL => 'grey',
        );

        return ArrayHelper::getValue($colors, $const, 'yellow');
    }

}
