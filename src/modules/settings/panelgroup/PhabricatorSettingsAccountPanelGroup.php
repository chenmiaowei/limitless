<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsAccountPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsAccountPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'account';

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return null;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 100;
    }

}
