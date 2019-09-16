<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 1:12 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\subscriptions\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontReloadResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\subscriptions\editor\PhabricatorSubscriptionsEditor;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngineLock;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class SubscriptionAddAction
 * @package orangins\modules\subscriptions\controllers
 * @author 陈妙威
 */
class SubscriptionEditAction extends SubscriptionAction
{
    /**
     * @param $phid
     * @author 陈妙威
     * @return AphrontResponse
     * @throws \yii\base\Exception
     * @throws \ReflectionException

     * @throws \Exception
     */
    public function run($phid)
    {
        $viewer = $this->controller->getViewer();
        $action = $this->id;

        if (!\Yii::$app->request->isPost) {
            return new Aphront400Response();
        }

        switch ($action) {
            case 'add':
                $is_add = true;
                break;
            case 'delete':
                $is_add = false;
                break;
            default:
                return new Aphront400Response();
        }

        $handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        $object = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            return $this->buildErrorResponse(
                \Yii::t("app",'Bad Object'),
                \Yii::t("app",'This object is not subscribable.'),
                $handle->getURI());
        }

        if ($object->isAutomaticallySubscribed($viewer->getPHID())) {
            return $this->buildErrorResponse(
                \Yii::t("app",'Automatically Subscribed'),
                \Yii::t("app",'You are automatically subscribed to this object.'),
                $handle->getURI());
        }

        if (!PhabricatorPolicyFilter::canInteract($viewer, $object)) {
            $lock = PhabricatorEditEngineLock::newForObject($viewer, $object);

            $dialog = $this->newDialog()
                ->addCancelButton($handle->getURI());

            return $lock->willBlockUserInteractionWithDialog($dialog);
        }

        if ($object instanceof PhabricatorApplicationTransactionInterface) {
            if ($is_add) {
                $xaction_value = array(
                    '+' => array($viewer->getPHID()),
                );
            } else {
                $xaction_value = array(
                    '-' => array($viewer->getPHID()),
                );
            }

            $xaction = $object->getApplicationTransactionTemplate()
                ->setTransactionType(PhabricatorTransactions::TYPE_SUBSCRIBERS)
                ->setNewValue($xaction_value);

            $editor = $object->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->setContentSourceFromRequest($this->controller->getRequest());

            $editor->applyTransactions(
                $object->getApplicationTransactionObject(),
                array($xaction));
        } else {

            // TODO: Eventually, get rid of this once everything implements
            // PhabricatorApplicationTransactionInterface.

            $editor = (new PhabricatorSubscriptionsEditor())
                ->setActor($viewer)
                ->setObject($object);

            if ($is_add) {
                $editor->subscribeExplicit(array($viewer->getPHID()));
            } else {
                $editor->unsubscribe(array($viewer->getPHID()));
            }

            $editor->save();
        }

        // TODO: We should just render the "Unsubscribe" action and swap it out
        // in the document for Ajax requests.
        return (new AphrontReloadResponse())->setURI($handle->getURI());
    }

    /**
     * @param $title
     * @param $message
     * @param $uri
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function buildErrorResponse($title, $message, $uri) {
        $viewer = $this->controller->getViewer();

        $dialog = (new AphrontDialogView())
            ->setTitle($title)
            ->addCancelButton($uri)
            ->appendChild($message)
            ->setViewer($viewer);

        return (new AphrontDialogResponse())
            ->setDialog($dialog);
    }
}