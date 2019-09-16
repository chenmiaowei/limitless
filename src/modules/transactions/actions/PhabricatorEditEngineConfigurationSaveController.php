<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;

/**
 * Class PhabricatorEditEngineConfigurationSaveController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationSaveController
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
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $engine_key = $request->getURIData('engineKey');
        $this->setEngineKey($engine_key);

        $key = $request->getURIData('key');
        $viewer = $this->getViewer();

        $config = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys(array($engine_key))
            ->withIdentifiers(array($key))
            ->executeOne();
        if (!$config) {
            return (new Aphront404Response());
        }

        $view_uri = $config->getURI();

        if ($config->getID()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app",'Already Editable'))
                ->appendParagraph(
                    \Yii::t("app",'This form configuration is already editable.'))
                ->addCancelButton($view_uri);
        }

        if ($request->isFormPost()) {
            $editor = (new PhabricatorEditEngineConfigurationEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true);

            $editor->applyTransactions($config, array());

            return (new AphrontRedirectResponse())
                ->setURI($config->getURI());
        }

        // TODO: Explain what this means in more detail once the implications are
        // more clear, or just link to some docs or something.

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Make Builtin Editable'))
            ->appendParagraph(
                \Yii::t("app",'Make this builtin form editable?'))
            ->addSubmitButton(\Yii::t("app",'Make Editable'))
            ->addCancelButton($view_uri);
    }

}
