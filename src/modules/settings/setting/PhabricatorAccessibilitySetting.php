<?php

namespace orangins\modules\settings\setting;

use orangins\modules\celerity\postprocessor\CelerityDefaultPostprocessor;
use orangins\modules\celerity\postprocessor\CelerityPostprocessor;
use orangins\modules\settings\panel\PhabricatorDisplayPreferencesSettingsPanel;

/**
 * Class PhabricatorAccessibilitySetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorAccessibilitySetting
    extends PhabricatorSelectSetting
{

    /**
     *
     */
    const SETTINGKEY = 'resource-postprocessor';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Accessibility');
    }

    /**
     * @return null|string
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
        return 100;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return \Yii::t("app",
            'If you have difficulty reading the Phabricator UI, this setting ' .
            'may make Phabricator more accessible.');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return CelerityDefaultPostprocessor::POSTPROCESSOR_KEY;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getSelectOptions()
    {
        $postprocessor_map = CelerityPostprocessor::getAllPostprocessors();

        $postprocessor_map = mpull($postprocessor_map, 'getPostprocessorName');
        asort($postprocessor_map);

        $postprocessor_order = array(
            CelerityDefaultPostprocessor::POSTPROCESSOR_KEY,
        );

        $postprocessor_map = array_select_keys(
                $postprocessor_map,
                $postprocessor_order) + $postprocessor_map;

        return $postprocessor_map;
    }

}
