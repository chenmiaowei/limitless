<?php

namespace orangins\modules\dashboard\actions\panel;

use Filesystem;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\editors\PhabricatorDashboardPanelTransactionEditor;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardTabsPanelType;
use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\dashboard\typeahead\PhabricatorDashboardPanelDatasource;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardTabsPanelTabsTransaction;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardPanelTabsController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelTabsController
    extends PhabricatorDashboardController
{

    /**
     * @var
     */
    private $contextObject;

    /**
     * @param $context_object
     * @return $this
     * @author 陈妙威
     */
    private function setContextObject($context_object)
    {
        $this->contextObject = $context_object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getContextObject()
    {
        return $this->contextObject;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        /** @var PhabricatorDashboardPanel $panel */
        $panel = PhabricatorDashboardPanel::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$panel) {
            return new Aphront404Response();
        }

        $tabs_type = (new PhabricatorDashboardTabsPanelType())
            ->getPanelTypeKey();

        // This controller may only be used to edit tab panels.
        $panel_type = $panel->getPanelType();
        if ($panel_type !== $tabs_type) {
            return new Aphront404Response();
        }

        $op = $request->getURIData('op');
        $after = $request->getStr('after');
        if (!strlen($after)) {
            $after = null;
        }

        $target = $request->getStr('target');
        if (!strlen($target)) {
            $target = null;
        }

        $impl = $panel->getImplementation();
        $config = $impl->getPanelConfiguration($panel);

        $cancel_uri = $panel->getURI();

        if ($after !== null) {
            $found = false;
            foreach ($config as $key => $spec) {
                if ((string)$key === $after) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $this->newDialog()
                    ->setTitle(\Yii::t("app",'Adjacent Tab Not Found'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'Adjacent tab ("%s") was not found on this panel. It may have ' .
                            'been removed.',
                            $after))
                    ->addCancelButton($cancel_uri);
            }
        }

        if ($target !== null) {
            $found = false;
            foreach ($config as $key => $spec) {
                if ((string)$key === $target) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $this->newDialog()
                    ->setTitle(\Yii::t("app",'Target Tab Not Found'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'Target tab ("%s") was not found on this panel. It may have ' .
                            'been removed.',
                            $target))
                    ->addCancelButton($cancel_uri);
            }
        }

        // Tab panels may be edited from the panel page, or from the context of
        // a dashboard. If we're editing from a dashboard, we want to redirect
        // back to the dashboard after making changes.

        $context_phid = $request->getStr('contextPHID');
        $context = null;
        if (strlen($context_phid)) {
            $context = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($context_phid))
                ->executeOne();
            if (!$context) {
                return new Aphront404Response();
            }

            switch (PhabricatorPHID::phid_get_type($context_phid)) {
                case PhabricatorDashboardDashboardPHIDType::TYPECONST:
                    $cancel_uri = $context->getURI();
                    break;
                case PhabricatorDashboardPanelPHIDType::TYPECONST:
                    $cancel_uri = $context->getURI();
                    break;
                default:
                    return $this->newDialog()
                        ->setTitle(\Yii::t("app",'Context Object Unsupported'))
                        ->appendParagraph(
                            \Yii::t("app",
                                'Context object ("%s") has unsupported type. Panels should ' .
                                'be rendered from the context of a dashboard or another ' .
                                'panel.',
                                $context_phid))
                        ->addCancelButton($cancel_uri);
            }

            $this->setContextObject($context);
        }

        switch ($op) {
            case 'add':
                return $this->handleAddOperation($panel, $after, $cancel_uri);
            case 'remove':
                return $this->handleRemoveOperation($panel, $target, $cancel_uri);
            case 'move':
                break;
            case 'rename':
                return $this->handleRenameOperation($panel, $target, $cancel_uri);
        }
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param $after
     * @param $cancel_uri
     * @return AphrontRedirectResponse|AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleAddOperation(
        PhabricatorDashboardPanel $panel,
        $after,
        $cancel_uri)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $panel_phid = null;
        $errors = array();
        if ($request->isFormPost()) {
            $panel_phid = $request->getArr('panelPHID');
            $panel_phid = head($panel_phid);

            $add_panel = PhabricatorDashboardPanel::find()
                ->setViewer($viewer)
                ->withPHIDs(array($panel_phid))
                ->executeOne();
            if (!$add_panel) {
                $errors[] = \Yii::t("app",'You must select a valid panel.');
            }

            if (!$errors) {
                $add_panel_config = array(
                    'name' => null,
                    'panelID' => $add_panel->getID(),
                );
                $add_panel_key = Filesystem::readRandomCharacters(12);

                $impl = $panel->getImplementation();
                $old_config = $impl->getPanelConfiguration($panel);
                $new_config = array();
                if ($after === null) {
                    $new_config = $old_config;
                    $new_config[] = $add_panel_config;
                } else {
                    foreach ($old_config as $key => $value) {
                        $new_config[$key] = $value;
                        if ((string)$key === $after) {
                            $new_config[$add_panel_key] = $add_panel_config;
                        }
                    }
                }

                $xactions = array();

                $xactions[] = $panel->getApplicationTransactionTemplate()
                    ->setTransactionType(
                        PhabricatorDashboardTabsPanelTabsTransaction::TRANSACTIONTYPE)
                    ->setNewValue($new_config);

                $editor = (new PhabricatorDashboardPanelTransactionEditor())
                    ->setContentSourceFromRequest($request)
                    ->setActor($viewer)
                    ->setContinueOnNoEffect(true)
                    ->setContinueOnMissingFields(true);

                $editor->applyTransactions($panel, $xactions);

                return (new AphrontRedirectResponse())->setURI($cancel_uri);
            }
        }

        if ($panel_phid) {
            $v_panel = array($panel_phid);
        } else {
            $v_panel = array();
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setDatasource(new PhabricatorDashboardPanelDatasource())
                    ->setLimit(1)
                    ->setName('panelPHID')
                    ->setLabel(\Yii::t("app",'Panel'))
                    ->setValue($v_panel));

        return $this->newEditDialog()
            ->setTitle(\Yii::t("app",'Choose Dashboard Panel'))
            ->setErrors($errors)
            ->addHiddenInput('after', $after)
            ->appendForm($form)
            ->addCancelButton($cancel_uri)
            ->addSubmitButton(\Yii::t("app",'Add Panel'));
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param $target
     * @param $cancel_uri
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleRemoveOperation(
        PhabricatorDashboardPanel $panel,
        $target,
        $cancel_uri)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $panel_phid = null;
        $errors = array();
        if ($request->isFormPost()) {
            $impl = $panel->getImplementation();
            $old_config = $impl->getPanelConfiguration($panel);

            $new_config = $this->removePanel($old_config, $target);
            $this->writePanelConfig($panel, $new_config);

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        return $this->newEditDialog()
            ->setTitle(\Yii::t("app",'Remove tab?'))
            ->addHiddenInput('target', $target)
            ->appendParagraph(\Yii::t("app",'Really remove this tab?'))
            ->addCancelButton($cancel_uri)
            ->addSubmitButton(\Yii::t("app",'Remove Tab'));
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param $target
     * @param $cancel_uri
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleRenameOperation(
        PhabricatorDashboardPanel $panel,
        $target,
        $cancel_uri)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $impl = $panel->getImplementation();
        $old_config = $impl->getPanelConfiguration($panel);

        $spec = $old_config[$target];
        $name = ArrayHelper::getValue($spec, 'name');

        if ($request->isFormPost()) {
            $name = $request->getStr('name');

            $new_config = $this->renamePanel($old_config, $target, $name);
            $this->writePanelConfig($panel, $new_config);

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendControl(
                (new AphrontFormTextControl())
                    ->setValue($name)
                    ->setName('name')
                    ->setLabel(\Yii::t("app",'Tab Name')));

        return $this->newEditDialog()
            ->setTitle(\Yii::t("app",'Rename Panel'))
            ->addHiddenInput('target', $target)
            ->appendForm($form)
            ->addCancelButton($cancel_uri)
            ->addSubmitButton(\Yii::t("app",'Rename Tab'));
    }


    /**
     * @param PhabricatorDashboardPanel $panel
     * @param array $config
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function writePanelConfig(
        PhabricatorDashboardPanel $panel,
        array $config)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $xactions = array();

        $xactions[] = $panel->getApplicationTransactionTemplate()
            ->setTransactionType(
                PhabricatorDashboardTabsPanelTabsTransaction::TRANSACTIONTYPE)
            ->setNewValue($config);

        $editor = (new PhabricatorDashboardPanelTransactionEditor())
            ->setContentSourceFromRequest($request)
            ->setActor($viewer)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        return $editor->applyTransactions($panel, $xactions);
    }

    /**
     * @param array $config
     * @param $target
     * @return array
     * @author 陈妙威
     */
    private function removePanel(array $config, $target)
    {
        $result = array();

        foreach ($config as $key => $panel_spec) {
            if ((string)$key === $target) {
                continue;
            }
            $result[$key] = $panel_spec;
        }

        return $result;
    }

    /**
     * @param array $config
     * @param $target
     * @param $name
     * @return array
     * @author 陈妙威
     */
    private function renamePanel(array $config, $target, $name)
    {
        $config[$target]['name'] = $name;
        return $config;
    }

    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @author 陈妙威
     */
    protected function newEditDialog()
    {
        $dialog = $this->newDialog()
            ->setWidth(AphrontDialogView::WIDTH_FORM);

        $context = $this->getContextObject();
        if ($context) {
            $dialog->addHiddenInput('contextPHID', $context->getPHID());
        }

        return $dialog;
    }

}
