<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationDefaultsController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationDefaultsController
    extends PhabricatorEditEngineController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
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

        $engine = $config->getEngine();
        $fields = $engine->getFieldsForConfig($config);

        foreach ($fields as $key => $field) {
            if (!$field->getIsFormField()) {
                unset($fields[$key]);
                continue;
            }

            if (!$field->getIsDefaultable()) {
                unset($fields[$key]);
                continue;
            }
        }

        foreach ($fields as $field) {
            $field->setIsEditDefaults(true);
        }

        if ($request->isFormPost()) {
            $xactions = array();

            foreach ($fields as $field) {
                $field->readValueFromSubmit($request);
            }

            $type = PhabricatorEditEngineConfigurationTransaction::TYPE_DEFAULT;

            $xactions = array();
            foreach ($fields as $field) {
                $new_value = $field->getValueForDefaults();
                $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                    ->setTransactionType($type)
                    ->setMetadataValue('field.key', $field->getKey())
                    ->setNewValue($new_value);
            }

            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())
                ->setURI($cancel_uri);
        }

        $title = \Yii::t("app",'Edit Form Defaults');

        $form = (new AphrontFormView())
            ->setUser($viewer);

        foreach ($fields as $field) {
            $field->appendToForm($form);
        }

        $form
            ->appendControl(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app",'Save Defaults'))
                    ->addCancelButton($cancel_uri));

        $info = (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
            ->setErrors(
                array(
                    \Yii::t("app",'You are editing the default values for this form.'),
                ));


        $box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Form'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app",'Form %d', $config->getID()), $cancel_uri);
        $crumbs->addTextCrumb(\Yii::t("app",'Edit Defaults'));
        $crumbs->setBorder(true);

        $header = (new PHUIPageHeaderView())
            ->setHeader(\Yii::t("app",'Edit Form Defaults'))
            ->setHeaderIcon('fa-pencil');

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $info,
                $box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
