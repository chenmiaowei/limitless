<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 1:12 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\subscriptions\actions;


use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontReloadResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\edges\PhabricatorMutedByEdgeType;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class SubscriptionMuteAction
 * @package orangins\modules\subscriptions\controllers
 * @author 陈妙威
 */
class SubscriptionMuteAction extends SubscriptionAction
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

        $handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        /** @var PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object */
        $object = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            return new Aphront400Response();
        }

        $muted_type = PhabricatorMutedByEdgeType::EDGECONST;

        $edge_query = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs(array($object->getPHID()))
            ->withEdgeTypes(array($muted_type))
            ->withDestinationPHIDs(array($viewer->getPHID()));

        $edge_query->execute();

        $is_mute = !$edge_query->getDestinationPHIDs();
        $object_uri = $handle->getURI();

        if ($this->controller->getRequest()->isFormPost()) {
            if ($is_mute) {
                $xaction_value = array(
                    '+' => array_fuse(array($viewer->getPHID())),
                );
            } else {
                $xaction_value = array(
                    '-' => array_fuse(array($viewer->getPHID())),
                );
            }

            $xaction = $object->getApplicationTransactionTemplate()
                ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
                ->setMetadataValue('edge:type', $muted_type)
                ->setNewValue($xaction_value);

            $editor = $object->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->setContentSourceFromRequest($this->controller->getRequest());

            $editor->applyTransactions(
                $object->getApplicationTransactionObject(),
                array($xaction));

            return (new AphrontReloadResponse())->setURI($object_uri);
        }

        $dialog = (new AphrontDialogView())
            ->setViewer($viewer)
            ->addCancelButton($object_uri);

        if ($is_mute) {
            $dialog
                ->setTitle(\Yii::t("app", 'Mute Notifications'))
                ->appendParagraph(
                    \Yii::t("app",
                        'Mute this object? You will no longer receive notifications or ' .
                        'email about it.'))
                ->addSubmitButton(\Yii::t("app", 'Mute'));
        } else {
            $dialog
                ->setTitle(\Yii::t("app", 'Unmute Notifications'))
                ->appendParagraph(
                    \Yii::t("app",
                        'Unmute this object? You will receive notifications and email ' .
                        'again.'))
                ->addSubmitButton(\Yii::t("app", 'Unmute'));
        }
        return (new AphrontDialogResponse())
            ->setDialog($dialog);
    }
}