<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsAuthenticationPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsAuthenticationPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'authentication';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return \Yii::t("app",'Authentication');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 300;
    }

}
