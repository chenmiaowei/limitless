<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDateTimeSettingsPanel;

/**
 * Class PhabricatorWeekStartDaySetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorWeekStartDaySetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'week_start_day';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Week Starts On');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorDateTimeSettingsPanel::PANELKEY;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 400;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return \Yii::t("app",
            'Choose which day a calendar week should begin on.');
    }

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return 0;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            0 => \Yii::t("app", 'Sunday'),
            1 => \Yii::t("app", 'Monday'),
            2 => \Yii::t("app", 'Tuesday'),
            3 => \Yii::t("app", 'Wednesday'),
            4 => \Yii::t("app", 'Thursday'),
            5 => \Yii::t("app", 'Friday'),
            6 => \Yii::t("app", 'Saturday'),
        );
    }

}
