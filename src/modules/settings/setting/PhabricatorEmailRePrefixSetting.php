<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailFormatSettingsPanel;

/**
 * Class PhabricatorEmailRePrefixSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailRePrefixSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 're-prefix';

    /**
     *
     */
    const VALUE_RE_PREFIX = 're';
    /**
     *
     */
    const VALUE_NO_PREFIX = 'none';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Add "Re:" Prefix');
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
        return 200;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'The **Add "Re:" Prefix** setting adds "Re:" in front of all messages, ' .
            'even if they are not replies. If you use **Mail.app** on Mac OS X, ' .
            'this may improve mail threading.' .
            "\n\n" .
            "| Setting                | Example Mail Subject\n" .
            "|------------------------|----------------\n" .
            "| Enable \"Re:\" Prefix  | " .
            "`Re: [Differential] [Accepted] D123: Example Revision`\n" .
            "| Disable \"Re:\" Prefix | " .
            "`[Differential] [Accepted] D123: Example Revision`");
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_NO_PREFIX;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_RE_PREFIX => \Yii::t("app",'Enable "Re:" Prefix'),
            self::VALUE_NO_PREFIX => \Yii::t("app",'Disable "Re:" Prefix'),
        );
    }

}
