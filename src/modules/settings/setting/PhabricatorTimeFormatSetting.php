<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDateTimeSettingsPanel;

/**
 * Class PhabricatorTimeFormatSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorTimeFormatSetting
    extends PhabricatorSelectSetting
{
    /**
     *
     */
    const SETTINGKEY = 'time_format';
    /**
     *
     */
    const VALUE_FORMAT_12HOUR = 'g:i:s A';
    /**
     *
     */
    const VALUE_FORMAT_24HOUR = 'H:i:s';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Time Format');
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
        return 300;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return \Yii::t("app",
            'Select the format you prefer for editing and displaying time.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_FORMAT_12HOUR;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_FORMAT_12HOUR => \Yii::t("app", '12 Hour, 2:34 PM'),
            self::VALUE_FORMAT_24HOUR => \Yii::t("app", '24 Hour, 14:34'),
        );
    }

}
