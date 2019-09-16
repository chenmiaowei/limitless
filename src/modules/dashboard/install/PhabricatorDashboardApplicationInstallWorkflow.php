<?php

namespace orangins\modules\dashboard\install;

use orangins\lib\request\AphrontRequest;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

/**
 * Class PhabricatorDashboardApplicationInstallWorkflow
 * @package orangins\modules\dashboard\install
 * @author 陈妙威
 */
abstract class PhabricatorDashboardApplicationInstallWorkflow
    extends PhabricatorDashboardInstallWorkflow
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newApplication();

    /**
     * @return bool
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function canInstallToGlobalMenu()
    {
        return PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $this->newApplication(),
            PhabricatorPolicyCapability::CAN_EDIT);
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function handleRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $application = $this->newApplication();
        $can_global = $this->canInstallToGlobalMenu();

        switch ($this->getMode()) {
            case 'global':
                if (!$can_global) {
                    return $this->newGlobalPermissionDialog();
                } else if ($request->isFormPost()) {
                    return $this->installDashboard($application, null);
                } else {
                    return $this->newGlobalConfirmDialog();
                }
            case 'personal':
                if ($request->isFormPost()) {
                    return $this->installDashboard($application, $viewer->getPHID());
                } else {
                    return $this->newPersonalConfirmDialog();
                }
        }

        $global_item = $this->newGlobalMenuItem()
            ->setDisabled(!$can_global);

        $menu = $this->newMenuFromItemMap(
            array(
                'personal' => $this->newPersonalMenuItem(),
                'global' => $global_item,
            ));

        return $this
            ->newApplicationModeDialog()
            ->appendChild($menu);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newGlobalPermissionDialog();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newGlobalConfirmDialog();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newPersonalConfirmDialog();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newPersonalMenuItem();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newGlobalMenuItem();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newApplicationModeDialog();

}
