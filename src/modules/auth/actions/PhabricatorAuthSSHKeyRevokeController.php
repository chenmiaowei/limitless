<?php

namespace orangins\modules\auth\actions;


use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\editor\PhabricatorAuthSSHKeyEditor;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorAuthSSHKeyRevokeController
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyRevokeController
    extends PhabricatorAuthSSHKeyAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView|Aphront404Response
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
        $viewer = $this->getViewer();

        $key = PhabricatorAuthSSHKey::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$key) {
            return new Aphront404Response();
        }

        $cancel_uri = $key->getURI();

        $token = (new PhabricatorAuthSessionEngine())
            ->requireHighSecuritySession(
                $viewer,
                $request,
                $cancel_uri);

        if ($request->isFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
                ->setTransactionType(PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE)
                ->setNewValue(true);

            (new PhabricatorAuthSSHKeyEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->applyTransactions($key, $xactions);

            return (new AphrontRedirectResponse())->setURI($cancel_uri);
        }

        $name = phutil_tag('strong', array(), $key->getName());

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Revoke SSH Public Key'))
            ->appendParagraph(
                \Yii::t("app",
                    'The key "%s" will be permanently revoked, and you will no ' .
                    'longer be able to use the corresponding private key to ' .
                    'authenticate.',
                    $name))
            ->addSubmitButton(\Yii::t("app", 'Revoke Public Key'))
            ->addCancelButton($cancel_uri);
    }

}
