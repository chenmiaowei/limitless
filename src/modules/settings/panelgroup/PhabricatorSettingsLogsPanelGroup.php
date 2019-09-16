<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsLogsPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsLogsPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'logs';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return \Yii::t("app",'Sessions and Logs');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 600;
    }

}
