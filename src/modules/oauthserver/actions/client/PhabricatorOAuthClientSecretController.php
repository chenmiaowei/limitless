<?php

namespace orangins\modules\oauthserver\actions\client;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorOAuthClientSecretController
 * @package orangins\modules\oauthserver\actions\client
 * @author 陈妙威
 */
final class PhabricatorOAuthClientSecretController
    extends PhabricatorOAuthClientController
{

    /**
     * @return Aphront404Response|AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $client = PhabricatorOAuthServerClient::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$client) {
            return new Aphront404Response();
        }

        $view_uri = $client->getViewURI();
        $token = (new  PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $view_uri);

        if ($request->isFormPost()) {
            $secret = $client->getSecret();

            $body = (new  PHUIFormLayoutView())
                ->appendChild(
                    (new  AphrontFormTextAreaControl())
                        ->setLabel(pht('Plaintext'))
                        ->setReadOnly(true)
                        ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
                        ->setValue($secret));

            return $this->newDialog()
                ->setWidth(AphrontDialogView::WIDTH_FORM)
                ->setTitle(pht('Application Secret'))
                ->appendChild($body)
                ->addCancelButton($view_uri, pht('Done'));
        }


        $is_serious = PhabricatorEnv::getEnvConfig('phabricator.serious-business');

        if ($is_serious) {
            $body = pht(
                'The secret associated with this OAuth application will be shown in ' .
                'plain text on your screen.');
        } else {
            $body = pht(
                'The secret associated with this OAuth application will be shown in ' .
                'plain text on your screen. Before continuing, wrap your arms around ' .
                'your monitor to create a human shield, keeping it safe from prying ' .
                'eyes. Protect company secrets!');
        }

        return $this->newDialog()
            ->setTitle(pht('Really show application secret?'))
            ->appendChild($body)
            ->addSubmitButton(pht('Show Application Secret'))
            ->addCancelButton($view_uri);
    }

}
