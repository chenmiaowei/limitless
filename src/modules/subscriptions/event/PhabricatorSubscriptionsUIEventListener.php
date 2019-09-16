<?php

namespace orangins\modules\subscriptions\event;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\events\constant\PhabricatorEventType;
use orangins\lib\events\PhabricatorEventListener;
use orangins\lib\events\RenderActionListEvent;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\transactions\edges\PhabricatorMutedByEdgeType;
use orangins\modules\transactions\edges\PhabricatorObjectHasSubscriberEdgeType;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\subscriptions\query\PhabricatorSubscribersQuery;
use orangins\modules\subscriptions\view\SubscriptionListStringBuilder;
use yii\base\Event;
use yii\helpers\Url;

/**
 * Class PhabricatorSubscriptionsUIEventListener
 * @package orangins\modules\subscriptions\event
 * @author 陈妙威
 */
final class PhabricatorSubscriptionsUIEventListener extends PhabricatorEventListener
{

    /**
     * @return mixed|void
     * @author 陈妙威
     */
    public function register()
    {
        $this->listen(PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS);
        $this->listen(PhabricatorEventType::TYPE_UI_WILLRENDERPROPERTIES);
    }

    /**
     * @param Event|RenderActionListEvent $event
     * @return mixed|void
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function handleEvent(Event $event)
    {
        $object = $event->getObject();

        switch ($event->name) {
            case PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS:
                $this->handleActionEvent($event);
                break;
            case PhabricatorEventType::TYPE_UI_WILLRENDERPROPERTIES:
                // Hacky solution so that property list view on Diffusion
                // commits shows build status, but not Projects, Subscriptions,
                // or Tokens.
                $this->handlePropertyEvent($event);
                break;
        }
    }

    /**
     * @param RenderActionListEvent $event
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function handleActionEvent($event)
    {
        $user = $event->getUser();
        $user_phid = $user->getPHID();
        $object = $event->getObject();

        if (!$object || !$object->getPHID()) {
            // No object, or the object has no PHID yet. No way to subscribe.
            return;
        }

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            // This object isn't subscribable.
            return;
        }

        $src_phid = $object->getPHID();
        $subscribed_type = PhabricatorObjectHasSubscriberEdgeType::EDGECONST;
        $muted_type = PhabricatorMutedByEdgeType::EDGECONST;

        $edges = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs(array($src_phid))
            ->withEdgeTypes(
                array(
                    $subscribed_type,
                    $muted_type,
                ))
            ->withDestinationPHIDs(array($user_phid))
            ->execute();

        if ($user_phid) {
            $is_subscribed = isset($edges[$src_phid][$subscribed_type][$user_phid]);
            $is_muted = isset($edges[$src_phid][$muted_type][$user_phid]);
        } else {
            $is_subscribed = false;
            $is_muted = false;
        }

        if ($user_phid && $object->isAutomaticallySubscribed($user_phid)) {
            $sub_action = (new PhabricatorActionView())
                ->setWorkflow(true)
                ->setDisabled(true)
                ->setColor(PhabricatorActionView::TEXT_WHITE)
                ->setRenderAsForm(true)
                ->setHref(Url::to(['/subscriptions/index/add', 'phid' => $object->getPHID()]))
                ->setName(\Yii::t("app", 'Automatically Subscribed'))
                ->setIcon('fa-check-circle lightgreytext');
        } else {
            $can_interact = PhabricatorPolicyFilter::canInteract($user, $object);

            if ($is_subscribed) {
                $sub_action = (new PhabricatorActionView())
                    ->setWorkflow(true)
                    ->setColor(PhabricatorActionView::TEXT_WHITE)
                    ->setRenderAsForm(true)
                    ->setHref(Url::to(['/subscriptions/index/delete', 'phid' => $object->getPHID()]))
                    ->setName(\Yii::t("app", 'Unsubscribe'))
                    ->setIcon('fa-minus-circle')
                    ->setDisabled(!$can_interact);
            } else {
                $sub_action = (new PhabricatorActionView())
                    ->setWorkflow(true)
                    ->setColor(PhabricatorActionView::TEXT_WHITE)
                    ->setRenderAsForm(true)
                    ->setHref(Url::to(['/subscriptions/index/add', 'phid' => $object->getPHID()]))
                    ->setName(\Yii::t("app", 'Subscribe'))
                    ->setIcon('fa-plus-circle')
                    ->setDisabled(!$can_interact);
            }

            if (!$user->isLoggedIn()) {
                $sub_action->setDisabled(true);
            }
        }

        $mute_action = (new PhabricatorActionView())
            ->setWorkflow(true)
            ->setColor(PhabricatorActionView::TEXT_WHITE)
            ->setHref(Url::to(['/subscriptions/index/mute', 'phid' => $object->getPHID()]))
            ->setDisabled(!$user_phid);

        if (!$is_muted) {
            $mute_action
                ->setName(\Yii::t("app", 'Mute Notifications'))
                ->setIcon('fa-volume-up');
        } else {
            $mute_action
                ->setName(\Yii::t("app", 'Unmute Notifications'))
                ->setIcon('fa-volume-off')
                ->setColor(PhabricatorActionView::COLOR_WARNING);
        }

        $actions = $event->getActions();
        $actions[] = $sub_action;
        $actions[] = $mute_action;
        $event->setActions($actions);
    }

    /**
     * @param RenderActionListEvent $event
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function handlePropertyEvent($event)
    {
        $user = $event->getUser();
        $object = $event->getObject();

        if (!$object || !$object->getPHID()) {
            // No object, or the object has no PHID yet..
            return;
        }

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            // This object isn't subscribable.
            return;
        }

        $subscribers = PhabricatorSubscribersQuery::loadSubscribersForPHID(
            $object->getPHID());
        if ($subscribers) {
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($user)
                ->withPHIDs($subscribers)
                ->execute();
        } else {
            $handles = array();
        }
        $sub_view = (new SubscriptionListStringBuilder())
            ->setObjectPHID($object->getPHID())
            ->setHandles($handles)
            ->buildPropertyString();

        $view = $event->getView();
        $view->addProperty(\Yii::t("app", 'Subscribers'), new \PhutilSafeHTML($sub_view));
    }

}
