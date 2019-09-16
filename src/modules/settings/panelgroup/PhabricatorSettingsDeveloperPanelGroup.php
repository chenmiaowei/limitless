<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsDeveloperPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsDeveloperPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'developer';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return \Yii::t("app",'Developer');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 400;
    }

}
