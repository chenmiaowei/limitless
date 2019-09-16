<?php

namespace orangins\modules\dashboard\install;

use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\dashboard\engine\PhabricatorDashboardPortalProfileMenuEngine;

/**
 * Class PhabricatorDashboardPortalInstallWorkflow
 * @package orangins\modules\dashboard\install
 * @author 陈妙威
 */
final class PhabricatorDashboardPortalInstallWorkflow
    extends PhabricatorDashboardObjectInstallWorkflow
{

    /**
     *
     */
    const WORKFLOWKEY = 'portal';

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getOrder()
    {
        return 2000;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newWorkflowMenuItem()
    {
        return $this->newMenuItem()
            ->setHeader(pht('Add to Portal Menu'))
            ->setImageIcon('fa-compass')
            ->addAttribute(
                pht('Add this dashboard to the menu on a portal.'));
    }

    /**
     * @return mixed|PhabricatorDashboardPortalProfileMenuEngine
     * @author 陈妙威
     */
    protected function newProfileEngine()
    {
        return new PhabricatorDashboardPortalProfileMenuEngine();
    }

    /**
     * @author 陈妙威
     */
    protected function newQuery()
    {
        return PhabricatorDashboardPortal::find();
    }

    /**
     * @param $object
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newConfirmDialog($object)
    {
        return $this->newDialog()
            ->setTitle(pht('Add Dashboard to Portal Menu'))
            ->appendParagraph(
                pht(
                    'Add the dashboard %s to portal %s?',
                    $this->getDashboardDisplayName(),
                    phutil_tag('strong', array(), $object->getName())))
            ->addSubmitButton(pht('Add to Portal'));
    }

    /**
     * @param $object
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newObjectSelectionForm($object)
    {
        $viewer = $this->getViewer();

        if ($object) {
            $tokenizer_value = array($object->getPHID());
        } else {
            $tokenizer_value = array();
        }

        return (new AphrontFormView())
            ->setViewer($viewer)
            ->appendInstructions(
                pht(
                    'Select which portal you want to add the dashboard %s to.',
                    $this->getDashboardDisplayName()))
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setName('target')
                    ->setLimit(1)
                    ->setLabel(pht('Add to Portal'))
                    ->setValue($tokenizer_value)
                    ->setDatasource(new PhabricatorDashboardPortalDatasource()));
    }

}
