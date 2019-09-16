<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\panel\PhabricatorDisplayPreferencesSettingsPanel;

/**
 * Class PhabricatorMonospacedFontSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorMonospacedFontSetting
    extends PhabricatorStringSetting
{

    /**
     *
     */
    const SETTINGKEY = 'monospaced';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Monospaced Font');
    }

    /**
     * @return mixed
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
        return 500;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'You can customize the font used when showing monospaced text, ' .
            'including source code. You should enter a valid CSS font declaration ' .
            'like: `13px Consolas`');
    }

    /**
     * @param $value
     * @author 陈妙威
     */
    public function validateTransactionValue($value)
    {
        if (!strlen($value)) {
            return;
        }

        $filtered = self::filterMonospacedCSSRule($value);
        if ($filtered !== $value) {
            throw new Exception(
                \Yii::t("app",
                    'Monospaced font value "%s" is unsafe. You may only enter ' .
                    'letters, numbers, spaces, commas, periods, hyphens, ' .
                    'forward slashes, and double quotes',
                    $value));
        }
    }

    /**
     * @param $monospaced
     * @return null|string|string[]
     * @author 陈妙威
     */
    public static function filterMonospacedCSSRule($monospaced)
    {
        // Prevent the user from doing dangerous things.
        return preg_replace('([^a-z0-9 ,"./-]+)i', '', $monospaced);
    }

}
