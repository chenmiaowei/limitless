<?php

namespace orangins\modules\dashboard\install;

use orangins\modules\home\application\PhabricatorHomeApplication;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;

/**
 * Class PhabricatorDashboardHomeInstallWorkflow
 * @package orangins\modules\dashboard\install
 * @author 陈妙威
 */
final class PhabricatorDashboardHomeInstallWorkflow
    extends PhabricatorDashboardApplicationInstallWorkflow
{

    /**
     *
     */
    const WORKFLOWKEY = 'home';

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getOrder()
    {
        return 1000;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newWorkflowMenuItem()
    {
        return $this->newMenuItem()
            ->setHeader(pht('Add to Home Page Menu'))
            ->setImageIcon('fa-home')
            ->addAttribute(
                pht(
                    'Add this dashboard to the menu on the home page.'));
    }

    /**
     * @return mixed|PhabricatorHomeProfileMenuEngine
     * @author 陈妙威
     */
    protected function newProfileEngine()
    {
        return new PhabricatorHomeProfileMenuEngine();
    }

    /**
     * @return mixed|PhabricatorHomeApplication
     * @author 陈妙威
     * @throws \PhutilMethodNotImplementedException
     */
    protected function newApplication()
    {
        return new PhabricatorHomeApplication();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newApplicationModeDialog()
    {
        return $this->newDialog()
            ->addBodyClass('p-0')
            ->setTitle(pht('Add Dashboard to Home Menu'));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newPersonalMenuItem()
    {
        return $this->newMenuItem()
            ->setHeader(pht('Add to Personal Home Menu'))
            ->setImageIcon('fa-user')
            ->addAttribute(
                pht(
                    'Add this dashboard to your list of personal home menu items, ' .
                    'visible to only you.'));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newGlobalMenuItem()
    {
        return $this->newMenuItem()
            ->setHeader(pht('Add to Global Home Menu'))
            ->setImageIcon('fa-globe')
            ->addAttribute(
                pht(
                    'Add this dashboard to the global home menu, visible to all ' .
                    'users.'));
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    protected function newGlobalPermissionDialog()
    {
        return $this->newDialog()
            ->setTitle(pht('No Permission'))
            ->appendParagraph(
                pht(
                    'You do not have permission to install items on the global home ' .
                    'menu.'));
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newGlobalConfirmDialog()
    {
        return $this->newDialog()
            ->setTitle(pht('Add Dashboard to Global Home Page'))
            ->appendParagraph(
                pht(
                    'Add dashboard %s as a global menu item on the home page?',
                    $this->getDashboardDisplayName()))
            ->addSubmitButton(pht('Add to Home'));
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newPersonalConfirmDialog()
    {
        return $this->newDialog()
            ->setTitle(pht('Add Dashboard to Personal Home Page'))
            ->appendParagraph(
                pht(
                    'Add dashboard %s as a personal menu item on your home page?',
                    $this->getDashboardDisplayName()))
            ->addSubmitButton(pht('Add to Home'));
    }

}
