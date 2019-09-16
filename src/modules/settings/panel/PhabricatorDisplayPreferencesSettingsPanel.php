<?php

namespace orangins\modules\settings\panel;

use orangins\modules\settings\panelgroup\PhabricatorSettingsApplicationsPanelGroup;

/**
 * Class PhabricatorDisplayPreferencesSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorDisplayPreferencesSettingsPanel
    extends PhabricatorEditEngineSettingsPanel
{

    /**
     *
     */
    const PANELKEY = 'display';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Display Preferences');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsApplicationsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isTemplatePanel()
    {
        return true;
    }

}
