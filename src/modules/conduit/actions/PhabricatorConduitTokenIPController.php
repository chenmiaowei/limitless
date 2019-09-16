<?php

namespace orangins\modules\conduit\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\conduit\editors\PhabricatorConduitTokenEditor;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use orangins\modules\conduit\models\PhabricatorConduitTokenTransaction;
use orangins\modules\conduit\settings\PhabricatorConduitTokensSettingsPanel;
use orangins\modules\conduit\xaction\PhabricatorConduitTokenIPTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorConduitTokenEditController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitTokenIPController
    extends PhabricatorConduitController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
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
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        if (!$id) {
            return new Aphront404Response();
        }
        /** @var PhabricatorConduitToken $token */
        $token = PhabricatorConduitToken::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->withExpired(false)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$token) {
            return new Aphront404Response();
        }

        if (!$token->getPHID()) {
            $token->phid = $token->generatePHID();
            $token->save();
        }

        $object = $token->getObject();
        $title = \Yii::t("app", 'View API Token');


        $panel_uri = (new PhabricatorConduitTokensSettingsPanel())
            ->setViewer($viewer)
            ->setUser($object)
            ->getPanelURI();

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $panel_uri);


        $value = implode("\n", $token->getParameter('ip', []));
        $errors = [];
        if ($request->isFormPost()) {
            $value = $this->getRequest()->getStr('ip');

            $ip = explode("\n", $value);
            $ips = [];
            foreach ($ip as $item) {
                if (empty($item)) continue;
                $valid = filter_var($item, FILTER_VALIDATE_IP);
                if (!$valid) {
                    $errors[] = "'{$item}'不是有效的ip";
                } else {
                    $ips[] = $item;
                }
            }
            $value = implode("\n", $ips);
            if (count($errors) === 0) {

                $xactions = [];
                $xactions[] = (new PhabricatorConduitTokenTransaction)
                    ->setTransactionType(PhabricatorConduitTokenIPTransaction::TRANSACTIONTYPE)
                    ->setNewValue($ips);

                (new PhabricatorConduitTokenEditor())
                    ->setActor($request->getViewer())
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request)
                    ->applyTransactions($token, $xactions);

                return (new AphrontRedirectResponse())->setURI($panel_uri);
            }
        }

        $dialog = $this->newDialog()
            ->appendParagraph("多个IP之间以回车分割。")
            ->setErrors($errors)
            ->addClass("wmin-600")
            ->setTitle($title)
            ->appendChild((new AphrontFormTextAreaControl())
                ->setLabel("ip")
                ->setValue($value)
                ->setName("ip"))
            ->addCancelButton(\Yii::t('app', "Cancel"))
            ->addSubmitButton(\Yii::t('app', "Submit"))
            ->addHiddenInput('objectPHID', $object->getPHID());
        return $dialog;
    }

}
