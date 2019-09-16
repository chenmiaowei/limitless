<?php

namespace orangins\modules\config\constants;

use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigGroupConstants
 * @package orangins\modules\config\constants
 * @author 陈妙威
 */
abstract class PhabricatorConfigGroupConstants
    extends PhabricatorConfigConstants
{

    /**
     *
     */
    const GROUP_CORE = 'core';
    /**
     *
     */
    const GROUP_APPLICATION = 'apps';
    /**
     *
     */
    const GROUP_DEVELOPER = 'developer';

    /**
     * @param $group
     * @return object
     * @author 陈妙威
     */
    public static function getGroupName($group)
    {
        $map = array(
            self::GROUP_CORE => \Yii::t("app",'Core Settings'),
            self::GROUP_APPLICATION => \Yii::t("app",'Application Settings'),
            self::GROUP_DEVELOPER => \Yii::t("app",'Developer Settings'),
        );
        return ArrayHelper::getValue($map, $group, \Yii::t("app",'Unknown'));
    }

    /**
     * @param $group
     * @return object
     * @author 陈妙威
     */
    public static function getGroupShortName($group)
    {
        $map = array(
            self::GROUP_CORE => \Yii::t("app",'Core'),
            self::GROUP_APPLICATION => \Yii::t("app",'Application'),
            self::GROUP_DEVELOPER => \Yii::t("app",'Developer'),
        );
        return ArrayHelper::getValue($map, $group, \Yii::t("app",'Unknown'));
    }

    /**
     * @param $group
     * @return object
     * @author 陈妙威
     */
    public static function getGroupURI($group)
    {
        $map = array(
            self::GROUP_CORE => '/',
            self::GROUP_APPLICATION => 'application/',
            self::GROUP_DEVELOPER => 'developer/',
        );
        return ArrayHelper::getValue($map, $group, '#');
    }

    /**
     * @param $group
     * @return object
     * @author 陈妙威
     */
    public static function getGroupFullURI($group)
    {
        $map = array(
            self::GROUP_CORE => '/config/',
            self::GROUP_APPLICATION => '/config/application/',
            self::GROUP_DEVELOPER => '/config/developer/',
        );
        return ArrayHelper::getValue($map, $group, '#');
    }

}
