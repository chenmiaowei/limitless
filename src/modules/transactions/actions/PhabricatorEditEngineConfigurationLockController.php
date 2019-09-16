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
 * Class PhabricatorEditEngineConfigurationLockController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationLockController
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

        if ($request->isFormPost()) {
            $xactions = array();

            $locks = $request->getArr('locks');
            $type_locks = PhabricatorEditEngineConfigurationTransaction::TYPE_LOCKS;

            $xactions[] = (new PhabricatorEditEngineConfigurationTransaction())
                ->setTransactionType($type_locks)
                ->setNewValue($locks);

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

        $help = \Yii::t("app",<<<EOTEXT
**Locked** fields are visible in the form, but their values can not be changed
by the user.

**Hidden** fields are not visible in the form.

Any assigned default values are still respected, even if the field is locked
or hidden.
EOTEXT
        );

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions($help);

        $locks = $config->getFieldLocks();

        $lock_visible = PhabricatorEditEngineConfiguration::LOCK_VISIBLE;
        $lock_locked = PhabricatorEditEngineConfiguration::LOCK_LOCKED;
        $lock_hidden = PhabricatorEditEngineConfiguration::LOCK_HIDDEN;

        $map = array(
            $lock_visible => \Yii::t("app",'Visible'),
            $lock_locked => \Yii::t("app","\xF0\x9F\x94\x92 Locked"),
            $lock_hidden => \Yii::t("app","\xE2\x9C\x98 Hidden"),
        );

        foreach ($fields as $field) {
            if (!$field->getIsFormField()) {
                continue;
            }

            if (!$field->getIsLockable()) {
                continue;
            }

            $key = $field->getKey();

            $label = $field->getLabel();
            if (!strlen($label)) {
                $label = $key;
            }

            if ($field->getIsHidden()) {
                $value = $lock_hidden;
            } else if ($field->getIsLocked()) {
                $value = $lock_locked;
            } else {
                $value = $lock_visible;
            }

            $form->appendControl(
                (new AphrontFormSelectControl())
                    ->setName('locks[' . $key . ']')
                    ->setLabel($label)
                    ->setValue($value)
                    ->setOptions($map));
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'Lock / Hide Fields'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app",'Save Changes'))
            ->addCancelButton($cancel_uri);
    }

}
