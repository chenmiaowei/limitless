<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailDeliverySettingsPanel;

/**
 * Class PhabricatorEmailNotificationsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailNotificationsSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'no-mail';

    /**
     *
     */
    const VALUE_SEND_MAIL = '0';
    /**
     *
     */
    const VALUE_NO_MAIL = '1';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Email Notifications');
    }

    /**
     * @return null|string
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
        return 100;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'If you disable **Email Notifications**, Phabricator will never ' .
            'send email to notify you about events. This preference overrides ' .
            'all your other settings.' .
            "\n\n" .
            "//You will still receive some administrative email, like password " .
            "reset email.//");
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_SEND_MAIL;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_SEND_MAIL => \Yii::t("app",'Enable Email Notifications'),
            self::VALUE_NO_MAIL => \Yii::t("app",'Disable Email Notifications'),
        );
    }

}
