<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorEmailFormatSettingsPanel;

/**
 * Class PhabricatorEmailVarySubjectsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorEmailVarySubjectsSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'vary-subject';

    /**
     *
     */
    const VALUE_VARY_SUBJECTS = 'vary';
    /**
     *
     */
    const VALUE_STATIC_SUBJECTS = 'static';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Vary Subjects');
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
        return 300;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'With **Vary Subjects** enabled, most mail subject lines will include ' .
            'a brief description of their content, like `[Closed]` for a ' .
            'notification about someone closing a task.' .
            "\n\n" .
            "| Setting              | Example Mail Subject\n" .
            "|----------------------|----------------\n" .
            "| Vary Subjects        | " .
            "`[Maniphest] [Closed] T123: Example Task`\n" .
            "| Do Not Vary Subjects | " .
            "`[Maniphest] T123: Example Task`\n" .
            "\n" .
            'This can make mail more useful, but some clients have difficulty ' .
            'threading these messages. Disabling this option may improve ' .
            'threading at the cost of making subject lines less useful.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_VARY_SUBJECTS;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_VARY_SUBJECTS => \Yii::t("app",'Enable Vary Subjects'),
            self::VALUE_STATIC_SUBJECTS => \Yii::t("app",'Disable Vary Subjects'),
        );
    }

}
