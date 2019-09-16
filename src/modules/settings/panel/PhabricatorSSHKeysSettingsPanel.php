<?php

namespace orangins\modules\settings\panel;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use orangins\modules\auth\view\PhabricatorAuthSSHKeyTableView;
use orangins\modules\settings\panelgroup\PhabricatorSettingsAuthenticationPanelGroup;

/**
 * Class PhabricatorSSHKeysSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorSSHKeysSettingsPanel extends PhabricatorSettingsPanel
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        if ($this->getUser()->getIsMailingList()) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'ssh';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'SSH Public Keys');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsAuthenticationPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $user = $this->getUser();
        $viewer = $request->getViewer();

        $keys = PhabricatorAuthSSHKey::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($user->getPHID()))
            ->withIsActive(true)
            ->execute();

        $table = (new PhabricatorAuthSSHKeyTableView())
            ->setUser($viewer)
            ->setKeys($keys)
            ->setCanEdit(true)
            ->setNoDataString(\Yii::t("app","You haven't added any SSH Public Keys."));

        $panel = new PHUIObjectBoxView();
        $header = new PHUIHeaderView();

        $ssh_actions = PhabricatorAuthSSHKeyTableView::newKeyActionsMenu(
            $viewer,
            $user);

        return $this->newBox(\Yii::t("app",'SSH Public Keys'), $table, array($ssh_actions));
    }

}
