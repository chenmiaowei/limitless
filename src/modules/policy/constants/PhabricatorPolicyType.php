<?php

namespace orangins\modules\policy\constants;

use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPolicyType
 * @package orangins\modules\policy\constants
 * @author 陈妙威
 */
final class PhabricatorPolicyType extends PhabricatorPolicyConstants
{

    /**
     *
     */
    const TYPE_GLOBAL = 'global';
    /**
     *
     */
    const TYPE_OBJECT = 'object';
    /**
     *
     */
    const TYPE_USER = 'user';
    /**
     *
     */
    const TYPE_CUSTOM = 'custom';
    /**
     *
     */
    const TYPE_PROJECT = 'project';
    /**
     *
     */
    const TYPE_MASKED = 'masked';

    /**
     * @param $type
     * @return mixed
     * @author 陈妙威
     */
    public static function getPolicyTypeOrder($type)
    {
        static $map = array(
            self::TYPE_GLOBAL => 0,
            self::TYPE_OBJECT => 1,
            self::TYPE_USER => 2,
            self::TYPE_CUSTOM => 3,
            self::TYPE_PROJECT => 4,
            self::TYPE_MASKED => 9,
        );
        return ArrayHelper::getValue($map, $type, 9);
    }

    /**
     * @param $type
     * @return string
     * @author 陈妙威
     */
    public static function getPolicyTypeName($type)
    {
        switch ($type) {
            case self::TYPE_GLOBAL:
                return \Yii::t("app", 'Basic Policies');
            case self::TYPE_OBJECT:
                return \Yii::t("app", 'Object Policies');
            case self::TYPE_USER:
                return \Yii::t("app", 'User Policies');
            case self::TYPE_CUSTOM:
                return \Yii::t("app", 'Advanced');
            case self::TYPE_PROJECT:
                return \Yii::t("app", 'Members of Project...');
            case self::TYPE_MASKED:
            default:
                return \Yii::t("app", 'Other Policies');
        }
    }

}
