<?php

namespace orangins\modules\auth\actions\config;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\auth\capability\AuthManageProvidersCapability;
use orangins\modules\auth\editor\PhabricatorAuthProviderConfigEditor;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorAuthDisableAction
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
final class PhabricatorAuthDisableAction
    extends PhabricatorAuthProviderConfigAction
{

    /**
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
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
    public function run()
    {
        $request = $this->getRequest();
        $this->requireApplicationCapability(
            AuthManageProvidersCapability::CAPABILITY);
        $viewer = $request->getViewer();
        $config_id = $request->getURIData('id');
        $action = $request->getURIData('action');

        $config = PhabricatorAuthProviderConfig::find()
            ->withIDs(array($config_id))
            ->setViewer($viewer)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$config) {
            return new Aphront404Response();
        }

        $is_enable = ($action === 'enable');

        if ($request->isDialogFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                ->setTransactionType(
                    PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE)
                ->setNewValue((int)$is_enable);

            $editor = (new PhabricatorAuthProviderConfigEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->applyTransactions($config, $xactions);

            return (new AphrontRedirectResponse())->setURI(
                $this->getApplicationURI());
        }

        if ($is_enable) {
            $title = \Yii::t("app", 'Enable Provider?');
            if ($config->getShouldAllowRegistration()) {
                $body = \Yii::t("app",
                    'Do you want to enable this provider? Users will be able to use ' .
                    'their existing external accounts to register new Phabricator ' .
                    'accounts and log in using linked accounts.');
            } else {
                $body = \Yii::t("app",
                    'Do you want to enable this provider? Users will be able to log ' .
                    'in to Phabricator using linked accounts.');
            }
            $button = \Yii::t("app", 'Enable Provider');
        } else {
            // TODO: We could tailor this a bit more. In particular, we could
            // check if this is the last provider and either prevent if from
            // being disabled or force the user through like 35 prompts. We could
            // also check if it's the last provider linked to the acting user's
            // account and pop a warning like "YOU WILL NO LONGER BE ABLE TO LOGIN
            // YOU GOOF, YOU PROBABLY DO NOT MEAN TO DO THIS". None of this is
            // critical and we can wait to see how users manage to shoot themselves
            // in the feet. Shortly, `bin/auth` will be able to recover from these
            // types of mistakes.

            $title = \Yii::t("app", 'Disable Provider?');
            $body = \Yii::t("app",
                'Do you want to disable this provider? Users will not be able to ' .
                'register or log in using linked accounts. If there are any users ' .
                'without other linked authentication mechanisms, they will no longer ' .
                'be able to log in. If you disable all providers, no one will be ' .
                'able to log in.');
            $button = \Yii::t("app", 'Disable Provider');
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->appendChild($body)
            ->addCancelButton($this->getApplicationURI())
            ->addSubmitButton($button);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}
