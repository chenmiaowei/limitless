<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsApplicationsPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsApplicationsPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'applications';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return \Yii::t("app",'Applications');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 200;
    }

}
