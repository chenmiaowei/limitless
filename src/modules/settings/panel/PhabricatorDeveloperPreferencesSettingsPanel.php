<?php

namespace orangins\modules\settings\panel;

use orangins\modules\settings\panelgroup\PhabricatorSettingsDeveloperPanelGroup;

/**
 * Class PhabricatorDeveloperPreferencesSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorDeveloperPreferencesSettingsPanel
    extends PhabricatorEditEngineSettingsPanel
{

    /**
     *
     */
    const PANELKEY = 'developer';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Developer Settings');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsDeveloperPanelGroup::PANELGROUPKEY;
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
