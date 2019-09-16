<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDisplayPreferencesSettingsPanel;

/**
 * Class PhabricatorTitleGlyphsSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorTitleGlyphsSetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'titles';

    /**
     *
     */
    const VALUE_TITLE_GLYPHS = 'glyph';
    /**
     *
     */
    const VALUE_TITLE_TEXT = 'text';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app",'Page Titles');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorDisplayPreferencesSettingsPanel::PANELKEY;
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
     * @return null
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'Phabricator uses unicode glyphs in page titles to provide a compact ' .
            'representation of the current application. You can substitute plain ' .
            'text instead if these glyphs do not display on your system.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return self::VALUE_TITLE_GLYPHS;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        return array(
            self::VALUE_TITLE_GLYPHS => \Yii::t("app","Use Unicode Glyphs: \xE2\x9A\x99"),
            self::VALUE_TITLE_TEXT => \Yii::t("app",'Use Plain Text: [Differential]'),
        );
    }

}
