<?php
namespace orangins\modules\settings\panel;

use orangins\modules\settings\panelgroup\PhabricatorSettingsAccountPanelGroup;

/**
 * Class PhabricatorAccountSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorAccountSettingsPanel
  extends PhabricatorEditEngineSettingsPanel {

    /**
     *
     */
    const PANELKEY = 'account';

    /**
     * @return string
     * @author 陈妙威
     */public function getPanelName() {
    return \Yii::t("app",'Account');
  }

    /**
     * @return const|string
     * @author 陈妙威
     */public function getPanelGroupKey() {
    return PhabricatorSettingsAccountPanelGroup::PANELGROUPKEY;
  }

    /**
     * @return bool
     * @author 陈妙威
     */public function isManagementPanel() {
    return true;
  }

    /**
     * @return bool
     * @author 陈妙威
     */public function isTemplatePanel() {
    return true;
  }

}
