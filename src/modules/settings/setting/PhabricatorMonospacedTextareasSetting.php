<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDisplayPreferencesSettingsPanel;

final class PhabricatorMonospacedTextareasSetting
    extends PhabricatorSelectSetting
{

    const SETTINGKEY = 'monospaced-textareas';

    const VALUE_TEXT_VARIABLE_WIDTH = 'disabled';
    const VALUE_TEXT_MONOSPACED = 'enabled';

    public function getSettingName()
    {
        return \Yii::t("app", 'Monospaced Textareas');
    }

    public function getSettingPanelKey()
    {
        return PhabricatorDisplayPreferencesSettingsPanel::PANELKEY;
    }

    protected function getSettingOrder()
    {
        return 600;
    }

    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'You can choose to use either a monospaced or variable-width font ' .
            'in textareas in the UI. Textareas are used for editing descriptions ' .
            'and writing comments, among other things.');
    }

    public function getSettingDefaultValue()
    {
        return self::VALUE_TEXT_VARIABLE_WIDTH;
    }

    protected function getSelectOptions()
    {
        return array(
            self::VALUE_TEXT_VARIABLE_WIDTH => \Yii::t("app", 'Use Variable-Width Font'),
            self::VALUE_TEXT_MONOSPACED => \Yii::t("app", 'Use Monospaced Font'),
        );
    }


}
