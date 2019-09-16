<?php

namespace orangins\modules\settings\setting;

/**
 * Class PhabricatorDateFormatSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorSidebarToggleSetting
    extends PhabricatorSelectSetting
{
    /**
     *
     */
    const SETTINGKEY = 'sidebar_toggle';


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return "左边主菜单的缩放";
    }

//    /**
//     * @return null|string
//     * @author 陈妙威
//     */
//    public function getSettingPanelKey()
//    {
//        return PhabricatorDateTimeSettingsPanel::PANELKEY;
//    }

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
        return "选择左边主菜单的缩放";
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return 1;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            1 => '打开',
            0 => '缩小',
        );
    }
}
