<?php
namespace orangins\modules\dashboard\actions\dashboard;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\layoutconfig\PhabricatorDashboardPanelRef;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\typeahead\PhabricatorDashboardPanelDatasource;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardPanelsTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorDashboardAdjustController
 * @author 陈妙威
 */
final class PhabricatorDashboardAdjustController
    extends PhabricatorDashboardController
{

    /**
     * @var
     */
    private $contextPHID;
    /**
     * @var
     */
    private $panelKey;
    /**
     * @var
     */
    private $columnKey;

    /**
     * @return Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $context_phid = $request->getStr('contextPHID');

        /** @var PhabricatorDashboard$dashboard */
        $dashboard = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withPHIDs(array($context_phid))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$dashboard) {
            return new Aphront404Response();
        }

        $this->contextPHID = $context_phid;

        $done_uri = $dashboard->getURI();
        $ref_list = $dashboard->getPanelRefList();

        $panel_ref = null;
        $panel_key = $request->getStr('panelKey');
        if (strlen($panel_key)) {
            $panel_ref = $ref_list->getPanelRef($panel_key);
            if (!$panel_ref) {
                return new Aphront404Response();
            }

            $this->panelKey = $panel_key;
        }

        $column_key = $request->getStr('columnKey');
        if (strlen($column_key)) {
            $columns = $ref_list->getColumns();
            if (!isset($columns[$column_key])) {
                return new Aphront404Response();
            }
            $this->columnKey = $column_key;
        }

        $after_ref = null;
        $after_key = $request->getStr('afterKey');
        if (strlen($after_key)) {
            $after_ref = $ref_list->getPanelRef($after_key);
            if (!$after_ref) {
                return new Aphront404Response();
            }
        }

        switch ($request->getURIData('op')) {
            case 'add':
                return $this->handleAddRequest($dashboard, $done_uri);
            case 'remove':
                if (!$panel_ref) {
                    return new Aphront404Response();
                }
                return $this->handleRemoveRequest($dashboard, $panel_ref, $done_uri);
            case 'move':
                return $this->handleMoveRequest($dashboard, $panel_ref, $after_ref);
        }
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @param $done_uri
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
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleAddRequest(
        PhabricatorDashboard $dashboard,
        $done_uri)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $errors = array();

        $panel_phid = null;
        $e_panel = true;
        if ($request->isFormPost()) {
            $panel_phid = head($request->getArr('panelPHIDs'));

            if (!$panel_phid) {
                $errors[] = \Yii::t("app",'You must choose a panel to add to the dashboard.');
                $e_panel = \Yii::t("app",'Required');
            } else {
                $panel = PhabricatorDashboardPanel::find()
                    ->setViewer($viewer)
                    ->withPHIDs(array($panel_phid))
                    ->executeOne();
                if (!$panel) {
                    $errors[] = \Yii::t("app",'You must choose a valid panel.');
                    $e_panel = \Yii::t("app",'Invalid');
                }
            }

            if (!$errors) {
                $xactions = array();

                $ref_list = clone $dashboard->getPanelRefList();
                $ref_list->newPanelRef($panel, $this->columnKey);
                $new_panels = $ref_list->toDictionary();

                $xactions[] = $dashboard->getApplicationTransactionTemplate()
                    ->setTransactionType(
                        PhabricatorDashboardPanelsTransaction::TRANSACTIONTYPE)
                    ->setNewValue($new_panels);

                $editor = $dashboard->getApplicationTransactionEditor()
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnNoEffect(true)
                    ->setContinueOnMissingFields(true);

                $editor->applyTransactions($dashboard, $xactions);

                return (new AphrontRedirectResponse())->setURI($done_uri);
            }
        }

        if ($panel_phid) {
            $panel_phids = array($panel_phid);
        } else {
            $panel_phids = array();
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendRemarkupInstructions(
                \Yii::t("app",'Choose a panel to add to this dashboard:'))
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setDatasource(new PhabricatorDashboardPanelDatasource())
                    ->setLimit(1)
                    ->setName('panelPHIDs')
                    ->setLabel(\Yii::t("app",'Panel'))
                    ->setError($e_panel)
                    ->setValue($panel_phids));

        return $this->newEditDialog()
            ->setTitle(\Yii::t("app",'Add Panel'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setErrors($errors)
            ->appendForm($form)
            ->addCancelButton($done_uri)
            ->addSubmitButton(\Yii::t("app",'Add Panel'));
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @param PhabricatorDashboardPanelRef $panel_ref
     * @param $done_uri
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
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleRemoveRequest(
        PhabricatorDashboard $dashboard,
        PhabricatorDashboardPanelRef $panel_ref,
        $done_uri)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        // NOTE: If you can edit a dashboard, you can remove panels from it even
        // if you don't have permission to see them or they aren't valid. We only
        // require that the panel be present on the dashboard.

        if ($request->isFormPost()) {
            $xactions = array();

            $ref_list = clone $dashboard->getPanelRefList();
            $ref_list->removePanelRef($panel_ref);
            $new_panels = $ref_list->toDictionary();

            $xactions[] = $dashboard->getApplicationTransactionTemplate()
                ->setTransactionType(
                    PhabricatorDashboardPanelsTransaction::TRANSACTIONTYPE)
                ->setNewValue($new_panels);

            $editor = $dashboard->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true);

            $editor->applyTransactions($dashboard, $xactions);

            return (new AphrontRedirectResponse())->setURI($done_uri);
        }

        $panel_phid = $panel_ref->getPanelPHID();
        $handles = $viewer->loadHandles(array($panel_phid));
        $handle = $handles[$panel_phid];

        $message = \Yii::t("app",
            'Remove panel %s from dashboard %s?',
            phutil_tag('strong', array(), $handle->getFullName()),
            phutil_tag('strong', array(), $dashboard->getName()));

        return $this->newEditDialog()
            ->setTitle(\Yii::t("app",'Remove Dashboard Panel'))
            ->appendParagraph($message)
            ->addCancelButton($done_uri)
            ->addSubmitButton(\Yii::t("app",'Remove Panel'));
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @param PhabricatorDashboardPanelRef $panel_ref
     * @param PhabricatorDashboardPanelRef|null $after_ref
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
     * @throws \Exception
     * @author 陈妙威
     */
    private function handleMoveRequest(
        PhabricatorDashboard $dashboard,
        PhabricatorDashboardPanelRef $panel_ref,
        PhabricatorDashboardPanelRef $after_ref = null)
    {

        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $xactions = array();

        $ref_list = clone $dashboard->getPanelRefList();
        $ref_list->movePanelRef($panel_ref, $this->columnKey, $after_ref);
        $new_panels = $ref_list->toDictionary();

        $xactions[] = $dashboard->getApplicationTransactionTemplate()
            ->setTransactionType(
                PhabricatorDashboardPanelsTransaction::TRANSACTIONTYPE)
            ->setNewValue($new_panels);

        $editor = $dashboard->getApplicationTransactionEditor()
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        $editor->applyTransactions($dashboard, $xactions);

        return (new AphrontAjaxResponse())->setContent(array());
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newEditDialog()
    {
        return $this->newDialog()
            ->addHiddenInput('contextPHID', $this->contextPHID)
            ->addHiddenInput('panelKey', $this->panelKey)
            ->addHiddenInput('columnKey', $this->columnKey);
    }

}
