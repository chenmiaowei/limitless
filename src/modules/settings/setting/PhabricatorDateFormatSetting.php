<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDateTimeSettingsPanel;

/**
 * Class PhabricatorDateFormatSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorDateFormatSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'date_format';

    /**
     *
     */
    const VALUE_FORMAT_ISO = 'Y-m-d';
    /**
     *
     */
    const VALUE_FORMAT_US = 'n/j/Y';
    /**
     *
     */
    const VALUE_FORMAT_EUROPE = 'd-m-Y';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Date Format');
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
        return 200;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return \Yii::t("app",
            'Select the format you prefer for editing dates.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_FORMAT_ISO;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_FORMAT_ISO => \Yii::t("app", 'ISO 8601: 2000-02-28'),
            self::VALUE_FORMAT_US => \Yii::t("app", 'US: 2/28/2000'),
            self::VALUE_FORMAT_EUROPE => \Yii::t("app", 'Europe: 28-02-2000'),
        );
    }


}
