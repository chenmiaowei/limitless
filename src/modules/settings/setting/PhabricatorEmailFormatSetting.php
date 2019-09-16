<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailFormatSettingsPanel;

/**
 * Class PhabricatorEmailFormatSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailFormatSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'html-emails';

    /**
     *
     */
    const VALUE_HTML_EMAIL = 'html';
    /**
     *
     */
    const VALUE_TEXT_EMAIL = 'text';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'HTML Email');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorEmailFormatSettingsPanel::PANELKEY;
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
            'You can opt to receive plain text email from Phabricator instead ' .
            'of HTML email. Plain text email works better with some clients.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_HTML_EMAIL;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_HTML_EMAIL => \Yii::t("app",'Send HTML Email'),
            self::VALUE_TEXT_EMAIL => \Yii::t("app",'Send Plain Text Email'),
        );
    }

}
