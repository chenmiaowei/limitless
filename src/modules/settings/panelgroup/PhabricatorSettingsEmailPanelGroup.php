<?php

namespace orangins\modules\settings\panelgroup;

/**
 * Class PhabricatorSettingsEmailPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
final class PhabricatorSettingsEmailPanelGroup
    extends PhabricatorSettingsPanelGroup
{

    /**
     *
     */
    const PANELGROUPKEY = 'email';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelGroupName()
    {
        return \Yii::t("app",'Email');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 500;
    }

}
