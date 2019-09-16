<?php

namespace orangins\modules\settings\panel;

use orangins\modules\settings\panelgroup\PhabricatorSettingsEmailPanelGroup;

/**
 * Class PhabricatorEmailDeliverySettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorEmailDeliverySettingsPanel
    extends PhabricatorEditEngineSettingsPanel
{

    /**
     *
     */
    const PANELKEY = 'emaildelivery';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Email Delivery');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsEmailPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        if ($this->getUser()->getIsMailingList()) {
            return true;
        }

        return false;
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
