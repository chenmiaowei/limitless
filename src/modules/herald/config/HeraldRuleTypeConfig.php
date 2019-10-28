<?php

namespace orangins\modules\herald\config;

use orangins\lib\OranginsObject;
use Yii;

/**
 * Class HeraldRuleTypeConfig
 * @package orangins\modules\herald\config
 * @author 陈妙威
 */
final class HeraldRuleTypeConfig extends OranginsObject
{
    /**
     *
     */
    const RULE_TYPE_GLOBAL = 'global';
    /**
     *
     */
    const RULE_TYPE_OBJECT = 'object';
    /**
     *
     */
    const RULE_TYPE_PERSONAL = 'personal';

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getRuleTypeMap()
    {
        $map = array(
            self::RULE_TYPE_PERSONAL => Yii::t("app",'Personal'),
            self::RULE_TYPE_OBJECT => Yii::t("app",'Object'),
            self::RULE_TYPE_GLOBAL => Yii::t("app",'Global'),
        );
        return $map;
    }
}
