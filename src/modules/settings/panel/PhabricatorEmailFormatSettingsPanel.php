<?php

namespace orangins\modules\settings\panel;

use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\settings\panelgroup\PhabricatorSettingsEmailPanelGroup;

/**
 * Class PhabricatorEmailFormatSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorEmailFormatSettingsPanel
    extends PhabricatorEditEngineSettingsPanel
{

    /**
     *
     */
    const PANELKEY = 'emailformat';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Email Format');
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
     * @throws \yii\base\Exception
     */
    public function isUserPanel()
    {
        return PhabricatorMetaMTAMail::shouldMailEachRecipient();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        return false;
        /*
                if (!$this->isUserPanel()) {
              return false;
            }

            if ($this->getUser()->getIsMailingList()) {
              return true;
            }

            return false;
        */
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
