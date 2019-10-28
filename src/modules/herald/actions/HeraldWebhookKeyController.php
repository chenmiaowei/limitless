<?php

namespace orangins\modules\herald\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class HeraldWebhookKeyController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldWebhookKeyController
    extends HeraldWebhookController
{

    /**
     * @return mixed|Aphront404Response
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $hook = HeraldWebhook::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$hook) {
            return new Aphront404Response();
        }

        $action = $request->getURIData('action');
        if ($action === 'cycle') {
            if (!$request->isFormPost()) {
                return $this->newDialog()
                    ->setTitle(pht('Regenerate HMAC Key'))
                    ->appendParagraph(
                        pht(
                            'Regenerate the HMAC key used to sign requests made by this ' .
                            'webhook?'))
                    ->appendParagraph(
                        pht(
                            'Requests which are currently authenticated with the old key ' .
                            'may fail.'))
                    ->addCancelButton($hook->getURI())
                    ->addSubmitButton(pht('Regnerate Key'));
            } else {
                $hook->regenerateHMACKey()->save();
            }
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendControl(
                (new AphrontFormTextControl())
                    ->setLabel(pht('HMAC Key'))
                    ->setValue($hook->getHMACKey()));

        return $this->newDialog()
            ->setTitle(pht('Webhook HMAC Key'))
            ->appendForm($form)
            ->addCancelButton($hook->getURI(), pht('Done'));
    }


}
