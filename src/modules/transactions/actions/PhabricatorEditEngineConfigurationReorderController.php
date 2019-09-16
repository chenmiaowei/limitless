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
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\assets\JavelinEngineReorderBehaviorAsset;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationReorderController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationReorderController
    extends PhabricatorEditEngineController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
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
        $engine_key = $request->getURIData('engineKey');
        $this->setEngineKey($engine_key);

        $key = $request->getURIData('key');
        $viewer = $this->getViewer();

        /** @var PhabricatorEditEngineConfiguration $config */
        $config = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys(array($engine_key))
            ->withIdentifiers(array($key))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$config) {
            return (new Aphront404Response());
        }

        $cancel_uri = Url::to([
            "/transactions/editengine/view",
            "engineKey" => $engine_key,
            "key" => $key
        ]);
        $reorder_uri = Url::to([
            "/transactions/editengine/reorder",
            "engineKey" => $engine_key,
            "key" => $key
        ]);

        if ($request->isFormPost()) {
            $xactions = array();
            $key_order = $request->getStrList('keyOrder');

            $type_order = PhabricatorEditEngineConfigurationTransaction::TYPE_ORDER;

            $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                ->setTransactionType($type_order)
                ->setNewValue($key_order);

            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())
                ->setURI($cancel_uri);
        }

        $engine = $config->getEngine();
        $fields = $engine->getFieldsForConfig($config);


        $list_id = JavelinHtml::generateUniqueNodeId();
        $input_id = JavelinHtml::generateUniqueNodeId();

        $list = (new PHUIObjectItemListView())
            ->setUser($viewer)
            ->setID($list_id)
            ->setFlush(true);

        $key_order = array();
        foreach ($fields as $field) {
            if (!$field->getIsFormField()) {
                continue;
            }

            if (!$field->getIsReorderable()) {
                continue;
            }

            $label = $field->getLabel();
            $key = $field->getKey();

            if ($label !== null) {
                $header = $label;
            } else {
                $header = $key;
            }

            $item = (new PHUIObjectItemView())
                ->setHeader($header)
                ->setGrippable(true)
                ->addSigil('editengine-form-field')
                ->setMetadata(
                    array(
                        'fieldKey' => $key,
                    ));

            $list->addItem($item);

            $key_order[] = $key;
        }

        JavelinHtml::initBehavior(
            new JavelinEngineReorderBehaviorAsset(),
            array(
                'listID' => $list_id,
                'inputID' => $input_id,
                'reorderURI' => $reorder_uri,
            ));

        $note = (new PHUIInfoView())
            ->appendChild(\Yii::t("app",'Drag and drop fields to reorder them.'))
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE);

        $input = phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => 'keyOrder',
                'value' => implode(', ', $key_order),
                'id' => $input_id,
            ));

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Reorder Fields'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($note)
            ->appendChild($list)
            ->appendChild($input)
            ->addSubmitButton(\Yii::t("app",'Save Changes'))
            ->addCancelButton($cancel_uri);
    }

}
