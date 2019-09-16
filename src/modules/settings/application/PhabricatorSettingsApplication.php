<?php

namespace orangins\modules\settings\application;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\Url;
use orangins\lib\PhabricatorApplication;
use orangins\modules\settings\components\Setting;
use orangins\modules\settings\panel\PhabricatorAccountSettingsPanel;
use orangins\modules\settings\panel\PhabricatorDateTimeSettingsPanel;
use orangins\modules\settings\panelgroup\PhabricatorSettingsAccountPanelGroup;
use orangins\modules\settings\phid\PhabricatorUserPreferencesPHIDType;
use orangins\modules\settings\setting\PhabricatorDateFormatSetting;
use orangins\modules\settings\setting\PhabricatorPronounSetting;
use orangins\modules\settings\setting\PhabricatorTimeFormatSetting;
use orangins\modules\settings\setting\PhabricatorTimezoneSetting;
use orangins\modules\settings\setting\PhabricatorTranslationSetting;
use orangins\modules\settings\setting\PhabricatorWeekStartDaySetting;

/**
 * Class PhabricatorSettingsApplication
 * @package orangins\modules\application\models
 * @author 陈妙威
 */
final class PhabricatorSettingsApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'settings';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\settings\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/settings/index/index';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Settings');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'User Preferences');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-wrench';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_UTILITIES;
    }



    /**
     * @return string[]
     * @author 陈妙威
     */
    public function getSettings()
    {
        return [
            // 普通
            PhabricatorPronounSetting::class,
            PhabricatorTranslationSetting::class,

            // Date and Time
            PhabricatorDateFormatSetting::class,
            PhabricatorTimeFormatSetting::class,
            PhabricatorWeekStartDaySetting::class,
            PhabricatorTimezoneSetting::class,


        ];
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getSettingPanels()
    {
        return [
            PhabricatorAccountSettingsPanel::class,
            PhabricatorDateTimeSettingsPanel::class,
        ];
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getSettingPanelGroups()
    {
        return [
            PhabricatorSettingsAccountPanelGroup::class,
        ];
    }
}
