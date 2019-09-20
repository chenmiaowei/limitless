<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationSubtypeController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationSubtypeController
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
            return new Aphront404Response();
        }

        $cancel_uri = Url::to([
            "/transactions/editengine/view",
            "engineKey" => $engine_key,
            "key" => $key
        ]);

        $engine = $config->getEngine();
        if (!$engine->supportsSubtypes()) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {
            $xactions = array();

            $subtype = $request->getStr('subtype');
            $type_subtype =
                PhabricatorEditEngineConfigurationTransaction::TYPE_SUBTYPE;

            $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                ->setTransactionType($type_subtype)
                ->setNewValue($subtype);

            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        $fields = $engine->getFieldsForConfig($config);

        $help = \Yii::t("app",<<<EOTEXT
Choose the object **subtype** that this form should create and edit.
EOTEXT
        );

        $map = $engine->newSubtypeMap();
        $map = mpull($map, 'getName');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions($help)
            ->appendControl(
                (new AphrontFormSelectControl())
                    ->setName('subtype')
                    ->setLabel(\Yii::t("app",'Subtype'))
                    ->setValue($config->getSubtype())
                    ->setOptions($map));

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Change Form Subtype'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app",'Save Changes'))
            ->addCancelButton($cancel_uri);
    }

}
