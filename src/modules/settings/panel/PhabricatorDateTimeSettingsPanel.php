<?php

namespace orangins\modules\settings\panel;

use orangins\modules\settings\panelgroup\PhabricatorSettingsAccountPanelGroup;

/**
 * Class PhabricatorDateTimeSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorDateTimeSettingsPanel
    extends PhabricatorEditEngineSettingsPanel
{

    /**
     *
     */
    const PANELKEY = 'datetime';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Date and Time');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsAccountPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        return true;
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
