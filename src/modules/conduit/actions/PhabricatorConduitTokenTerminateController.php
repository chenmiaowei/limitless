<?php

namespace orangins\modules\conduit\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use orangins\modules\conduit\settings\PhabricatorConduitTokensSettingsPanel;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorConduitTokenTerminateController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitTokenTerminateController
    extends PhabricatorConduitController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $object_phid = $request->getStr('objectPHID');
        $id = $request->getURIData('id');

        if ($id) {
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

            $tokens = array($token);
            $object_phid = $token->getObjectPHID();

            $title = \Yii::t("app", 'Terminate API Token');
            $body = \Yii::t("app",
                'Really terminate this token? Any system using this token ' .
                'will no longer be able to make API requests.');
            $submit_button = \Yii::t("app", 'Terminate Token');
        } else {
            $tokens = PhabricatorConduitToken::find()
                ->setViewer($viewer)
                ->withObjectPHIDs(array($object_phid))
                ->withExpired(false)
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->execute();

            $title = \Yii::t("app", 'Terminate API Tokens');
            $body = \Yii::t("app",
                'Really terminate all active API tokens? Any systems using these ' .
                'tokens will no longer be able to make API requests.');
            $submit_button = \Yii::t("app", 'Terminate Tokens');
        }

        if ($object_phid != $viewer->getPHID()) {
            $object = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($object_phid))
                ->executeOne();
            if (!$object) {
                return new Aphront404Response();
            }
        } else {
            $object = $viewer;
        }

        $panel_uri = (new PhabricatorConduitTokensSettingsPanel())
            ->setViewer($viewer)
            ->setUser($object)
            ->getPanelURI();

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $panel_uri);

        if (!$tokens) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'No Tokens to Terminate'))
                ->appendParagraph(
                    \Yii::t("app", 'There are no API tokens to terminate.'))
                ->addCancelButton($panel_uri);
        }

        if ($request->isFormPost()) {
            foreach ($tokens as $token) {
                $token
                    ->setExpires(PhabricatorTime::getNow() - 60)
                    ->save();
            }
            return (new AphrontRedirectResponse())->setURI($panel_uri);
        }

        return $this->newDialog()
            ->setTitle($title)
            ->addHiddenInput('objectPHID', $object_phid)
            ->appendParagraph($body)
            ->addSubmitButton($submit_button)
            ->addCancelButton($panel_uri);
    }

}
