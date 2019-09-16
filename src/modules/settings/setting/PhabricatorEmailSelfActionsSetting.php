<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailDeliverySettingsPanel;

/**
 * Class PhabricatorEmailSelfActionsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailSelfActionsSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'self-mail';

    /**
     *
     */
    const VALUE_SEND_SELF = '0';
    /**
     *
     */
    const VALUE_NO_SELF = '1';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Self Actions');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorEmailDeliverySettingsPanel::PANELKEY;
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
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'If you disable **Self Actions**, Phabricator will not notify ' .
            'you about actions you take.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_SEND_SELF;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_SEND_SELF => \Yii::t("app",'Enable Self Action Mail'),
            self::VALUE_NO_SELF => \Yii::t("app",'Disable Self Action Mail'),
        );
    }

}
