<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationDisableController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationDisableController
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
        $viewer = $this->getViewer();

        $config = $this->loadConfigForEdit();
        if (!$config) {
            return (new Aphront404Response());
        }

        $engine_key = $config->getEngineKey();
        $key = $config->getIdentifier();
        $cancel_uri = Url::to([
            "/transactions/editengine/view",
            "engineKey" => $engine_key,
            "key" => $key
        ]);

        $type = PhabricatorEditEngineConfigurationTransaction::TYPE_DISABLE;

        if ($request->isFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                ->setTransactionType($type)
                ->setNewValue(!$config->getIsDisabled());

            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        if ($config->getIsDisabled()) {
            $title = \Yii::t("app", 'Enable Form');
            $body = \Yii::t("app",
                'Enable this form? Users who can see it will be able to use it to ' .
                'create objects.');
            $button = \Yii::t("app", 'Enable Form');
        } else {
            $title = \Yii::t("app", 'Disable Form');
            $body = \Yii::t("app",
                'Disable this form? Users will no longer be able to use it.');
            $button = \Yii::t("app", 'Disable Form');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->appendParagraph($body)
            ->addSubmitButton($button)
            ->addCancelbutton($cancel_uri);
    }
}
