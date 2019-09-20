<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use orangins\modules\transactions\query\PhabricatorEditEngineQuery;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationSortController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationSortController
    extends PhabricatorEditEngineController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|AphrontDialogView
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
        $engine_key = $request->getURIData('engineKey');
        $this->setEngineKey($engine_key);

        $type = $request->getURIData('type');
        $is_create = ($type == 'create');

        $engine = (new PhabricatorEditEngineQuery())
            ->setViewer($viewer)
            ->withEngineKeys(array($engine_key))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$engine) {
            return (new Aphront404Response());
        }

        $cancel_uri = "/transactions/editengine/{$engine_key}/";
        $reorder_uri = $reorder_uri = Url::to([
            "/transactions/editengine/sort",
            "engineKey" => $engine_key,
            "type" => $type
        ]);

        $query = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys(array($engine->getEngineKey()));

        if ($is_create) {
            $query->withIsDefault(true);
        } else {
            $query->withIsEdit(true);
        }

        /** @var PhabricatorEditEngineConfiguration[] $configs */
        $configs = $query->execute();

        // Do this check here (instead of in the Query above) to get a proper
        // policy exception if the user doesn't satisfy
        foreach ($configs as $config) {
            PhabricatorPolicyFilter::requireCapability(
                $viewer,
                $config,
                PhabricatorPolicyCapability::CAN_EDIT);
        }

        if ($is_create) {
            $configs = msort($configs, 'getCreateSortKey');
        } else {
            $configs = msort($configs, 'getEditSortKey');
        }

        if ($request->isFormPost()) {
            $form_order = $request->getStrList('formOrder');

            // NOTE: This has a side-effect of saving any factory-default forms
            // to the database. We might want to warn the user better, but this
            // shouldn't generally be very important or confusing.

            $configs = mpull($configs, null, 'getIdentifier');
            $configs = array_select_keys($configs, $form_order) + $configs;

            $order = 1;
            foreach ($configs as $config) {
                $xactions = array();

                if ($is_create) {
                    $xaction_type =
                        PhabricatorEditEngineConfigurationTransaction::TYPE_CREATEORDER;
                } else {
                    $xaction_type =
                        PhabricatorEditEngineConfigurationTransaction::TYPE_EDITORDER;
                }

                $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                    ->setTransactionType($xaction_type)
                    ->setNewValue($order);

                $editor = (new PhabricatorEditEngineConfigurationEditor())
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnNoEffect(true);

                $editor->applyTransactions($config, $xactions);

                $order++;
            }

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        $list_id = JavelinHtml::generateUniqueNodeId();
        $input_id = JavelinHtml::generateUniqueNodeId();

        $list = (new PHUIObjectItemListView())
            ->setUser($viewer)
            ->setID($list_id)
            ->setFlush(true);

        $form_order = array();
        foreach ($configs as $config) {
            $name = $config->getName();
            $identifier = $config->getIdentifier();

            $item = (new PHUIObjectItemView())
                ->setHeader($name)
                ->setGrippable(true)
                ->addSigil('editengine-form-config')
                ->setMetadata(
                    array(
                        'formIdentifier' => $identifier,
                    ));

            $list->addItem($item);

            $form_order[] = $identifier;
        }

        Javelin::initBehavior(
            'editengine-reorder-configs',
            array(
                'listID' => $list_id,
                'inputID' => $input_id,
                'reorderURI' => $reorder_uri,
            ));

        if ($is_create) {
            $title = \Yii::t("app",'Reorder Create Forms');
            $button = \Yii::t("app",'Save Create Order');

            $note_text = \Yii::t("app",
                'Drag and drop fields to change the order in which they appear in ' .
                'the application "Create" menu.');
        } else {
            $title = \Yii::t("app",'Reorder Edit Forms');
            $button = \Yii::t("app",'Save Edit Order');

            $note_text = \Yii::t("app",
                'Drag and drop fields to change their priority for edits. When a ' .
                'user edits an object, they will be shown the first form in this ' .
                'list that they have permission to see.');
        }

        $note = (new PHUIInfoView())
            ->appendChild($note_text)
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE);

        $input = phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => 'formOrder',
                'value' => implode(', ', $form_order),
                'id' => $input_id,
            ));

        return $this->newDialog()
            ->setTitle($title)
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($note)
            ->appendChild($list)
            ->appendChild($input)
            ->addSubmitButton(\Yii::t("app",'Save Changes'))
            ->addCancelButton($cancel_uri);
    }

}
