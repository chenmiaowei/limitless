<?php

namespace orangins\modules\dashboard\actions;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\dashboard\editors\PhabricatorDashboardPanelTransactionEditor;
use orangins\modules\dashboard\editors\PhabricatorDashboardTransactionEditor;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\models\PhabricatorDashboardPanelTransaction;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardQueryPanelType;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardPanelNameTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorNamedQuery;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardQueryPanelInstallController
 * @package orangins\modules\dashboard\actions
 * @author 陈妙威
 */
final class PhabricatorDashboardQueryPanelInstallController
    extends PhabricatorDashboardController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $v_dashboard = null;
        $v_name = null;
        $v_column = 0;
        $v_engine = $request->getURIData('engineKey');
        $v_query = $request->getURIData('queryKey');

        $e_name = true;

        // Validate Engines
        /** @var PhabricatorApplicationSearchEngine[] $engines */
        $engines = PhabricatorApplicationSearchEngine::getAllEngines();
        foreach ($engines as $name => $engine) {
            if (!$engine->canUseInPanelContext()) {
                unset($engines[$name]);
            }
        }
        if (!in_array($v_engine, array_keys($engines))) {
            return new Aphront404Response();
        }

        // Validate Queries
        /** @var PhabricatorApplicationSearchEngine $engine */
        $engine = $engines[$v_engine];
        $engine->setViewer($viewer);
        $good_query = false;
        if ($engine->isBuiltinQuery($v_query)) {
            $good_query = true;
        } else {
            $saved_query = PhabricatorSavedQuery::find()
                ->setViewer($viewer)
                ->withEngineClassNames(array($v_engine))
                ->withQueryKeys(array($v_query))
                ->executeOne();
            if ($saved_query) {
                $good_query = true;
            }
        }
        if (!$good_query) {
            return new Aphront404Response();
        }

        /** @var PhabricatorNamedQuery $named_query */
        $named_query = ArrayHelper::getValue($engine->loadEnabledNamedQueries(), $v_query);
        if ($named_query) {
            $v_name = $named_query->getQueryName();
        }

        $errors = array();

        if ($request->isFormPost()) {
            $v_dashboard = $request->getInt('dashboardID');
            $v_name = $request->getStr('name');
            if (!$v_name) {
                $errors[] = \Yii::t("app",'You must provide a name for this panel.');
                $e_name = \Yii::t("app",'Required');
            }

            $dashboard = PhabricatorDashboard::find()
                ->setViewer($viewer)
                ->withIDs(array($v_dashboard))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();

            if (!$dashboard) {
                $errors[] = \Yii::t("app",'Please select a valid dashboard.');
            }

            if (!$errors) {
                $redirect_uri = "/dashboard/view/{$v_dashboard}/";

                $panel_type = (new PhabricatorDashboardQueryPanelType())
                    ->getPanelTypeKey();
                $panel = PhabricatorDashboardPanel::initializeNewPanel($viewer);
                $panel->setPanelType($panel_type);

                $field_list = PhabricatorCustomField::getObjectFields(
                    $panel,
                    PhabricatorCustomField::ROLE_EDIT);

                $field_list
                    ->setViewer($viewer)
                    ->readFieldsFromStorage($panel);

                $panel->requireImplementation()->initializeFieldsFromRequest(
                    $panel,
                    $field_list,
                    $request);

                $xactions = array();

                $xactions[] = (new PhabricatorDashboardPanelTransaction())
                    ->setTransactionType(
                        PhabricatorDashboardPanelNameTransaction::TRANSACTIONTYPE)
                    ->setNewValue($v_name);

                $xactions[] = (new PhabricatorDashboardPanelTransaction())
                    ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
                    ->setMetadataValue('customfield:key', 'std:dashboard:core:class')
                    ->setOldValue(null)
                    ->setNewValue($v_engine);

                $xactions[] = (new PhabricatorDashboardPanelTransaction())
                    ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
                    ->setMetadataValue('customfield:key', 'std:dashboard:core:key')
                    ->setOldValue(null)
                    ->setNewValue($v_query);

                $editor = (new PhabricatorDashboardPanelTransactionEditor())
                    ->setActor($viewer)
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request)
                    ->applyTransactions($panel, $xactions);

                PhabricatorDashboardTransactionEditor::addPanelToDashboard(
                    $viewer,
                    PhabricatorContentSource::newFromRequest($request),
                    $panel,
                    $dashboard,
                    $request->getInt('column', 0));

                return (new AphrontRedirectResponse())->setURI($redirect_uri);
            }
        }

        // Make this a select for now, as we don't expect someone to have
        // edit access to a vast number of dashboards.
        // Can add optiongroup if needed down the road.
        $dashboards = PhabricatorDashboard::find()
            ->setViewer($viewer)
            ->withStatuses(array(
                PhabricatorDashboard::STATUS_ACTIVE,
            ))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->execute();
        $options = mpull($dashboards, 'getName', 'getID');
        asort($options);

        $redirect_uri = $engine->getQueryResultsPageURI($v_query);

        if (!$options) {
            $notice = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->appendChild(\Yii::t("app",'You do not have access to any dashboards. To ' .
                    'continue, please create a dashboard first.'));

            return $this->newDialog()
                ->setTitle(\Yii::t("app",'No Dashboards'))
                ->setWidth(AphrontDialogView::WIDTH_FORM)
                ->appendChild($notice)
                ->addCancelButton($redirect_uri);
        }

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->addHiddenInput('engine', $v_engine)
            ->addHiddenInput('query', $v_query)
            ->addHiddenInput('column', $v_column)
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app",'Name'))
                    ->setName('name')
                    ->setValue($v_name)
                    ->setError($e_name))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setUser($this->getViewer())
                    ->setValue($v_dashboard)
                    ->setName('dashboardID')
                    ->setOptions($options)
                    ->setLabel(\Yii::t("app",'Dashboard')));

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Add Panel to Dashboard'))
            ->setErrors($errors)
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($form->buildLayoutView())
            ->addCancelButton($redirect_uri)
            ->addSubmitButton(\Yii::t("app",'Add Panel'));

    }

}
