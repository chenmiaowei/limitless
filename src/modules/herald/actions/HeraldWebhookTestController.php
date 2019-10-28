<?php

namespace orangins\modules\herald\actions;

use Exception;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\models\HeraldWebhookRequest;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;
use PhutilInvalidStateException;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class HeraldWebhookTestController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldWebhookTestController
    extends HeraldWebhookController
{

    /**
     * @return mixed|Aphront404Response
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws Exception
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

        $v_object = null;
        $e_object = null;
        $errors = array();
        if ($request->isFormPost()) {

            $v_object = $request->getStr('object');
            if (!strlen($v_object)) {
                $object = $hook;
            } else {
                $objects = (new PhabricatorObjectQuery())
                    ->setViewer($viewer)
                    ->withNames(array($v_object))
                    ->execute();
                if ($objects) {
                    $object = head($objects);
                } else {
                    $e_object = pht('Invalid');
                    $errors[] = pht('Specified object could not be loaded.');
                }
            }

            if (!$errors) {
                $xaction_query =
                    PhabricatorApplicationTransactionQuery::newQueryForObject($object);

                $xactions = $xaction_query
                    ->withObjectPHIDs(array($object->getPHID()))
                    ->setViewer($viewer)
                    ->setLimit(10)
                    ->execute();

                $request = HeraldWebhookRequest::initializeNewWebhookRequest($hook)
                    ->setObjectPHID($object->getPHID())
                    ->setTriggerPHIDs(array($viewer->getPHID()))
                    ->setIsTestAction(true)
                    ->setTransactionPHIDs(mpull($xactions, 'getPHID'))
                    ->save();

                $request->queueCall();

                $next_uri = $hook->getURI() . 'request/' . $request->getID() . '/';

                return (new AphrontRedirectResponse())->setURI($next_uri);
            }
        }

        $instructions = <<<EOREMARKUP
Optionally, choose an object to generate test data for (like `D123` or `T234`).

The 10 most recent transactions for the object will be submitted to the webhook.
EOREMARKUP;

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendControl(
                (new AphrontFormTextControl())
                    ->setLabel(pht('Object'))
                    ->setName('object')
                    ->setError($e_object)
                    ->setValue($v_object));

        return $this->newDialog()
            ->setErrors($errors)
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setTitle(pht('New Test Request'))
            ->appendParagraph(new PHUIRemarkupView($viewer, $instructions))
            ->appendForm($form)
            ->addCancelButton($hook->getURI())
            ->addSubmitButton(pht('Test Webhook'));
    }


}
