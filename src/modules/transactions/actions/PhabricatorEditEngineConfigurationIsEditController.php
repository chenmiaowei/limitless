<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationIsEditController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationIsEditController
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

        $type = PhabricatorEditEngineConfigurationTransaction::TYPE_ISEDIT;

        if ($request->isFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                ->setTransactionType($type)
                ->setNewValue(!$config->getIsEdit());

            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())
                ->setURI($cancel_uri);
        }

        if ($config->getIsEdit()) {
            $title = \Yii::t("app",'Unmark as Edit Form');
            $body = \Yii::t("app",
                'Unmark this form as an edit form? It will no longer be able to be ' .
                'used to edit objects.');
            $button = \Yii::t("app",'Unmark Form');
        } else {
            $title = \Yii::t("app",'Mark as Edit Form');
            $body = \Yii::t("app",
                'Mark this form as an edit form? Users who can view it will be able ' .
                'to use it to edit objects.');
            $button = \Yii::t("app",'Mark Form');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->appendParagraph($body)
            ->addSubmitButton($button)
            ->addCancelbutton($cancel_uri);
    }
}
