<?php

namespace orangins\modules\subscriptions\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\view\extension\PHUICurtainExtension;
use orangins\modules\subscriptions\application\PhabricatorSubscriptionsApplication;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\subscriptions\query\PhabricatorSubscribersQuery;
use orangins\modules\subscriptions\view\SubscriptionListStringBuilder;
use PhutilSafeHTML;

/**
 * Class PhabricatorSubscriptionsCurtainExtension
 * @package orangins\modules\subscriptions\engineextension
 * @author 陈妙威
 */
final class PhabricatorSubscriptionsCurtainExtension
    extends PHUICurtainExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'subscriptions.subscribers';

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldEnableForObject($object)
    {
        return ($object instanceof PhabricatorSubscribableInterface);
    }

    /**
     * @return PhabricatorSubscriptionsApplication|mixed
     * @author 陈妙威
     * @throws \PhutilMethodNotImplementedException
     */
    public function getExtensionApplication()
    {
        return new PhabricatorSubscriptionsApplication();
    }

    /**
     * @param ActiveRecordPHID $object
     * @return \orangins\lib\view\layout\PHUICurtainPanelView

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildCurtainPanel($object)
    {
        $viewer = $this->getViewer();
        $object_phid = $object->getPHID();

        $subscriber_phids = PhabricatorSubscribersQuery::loadSubscribersForPHID(
            $object_phid);

        $handles = $viewer->loadHandles($subscriber_phids);

        // TODO: This class can't accept a HandleList yet.
        $handles = iterator_to_array($handles);

        $susbscribers_view = (new SubscriptionListStringBuilder())
            ->setObjectPHID($object_phid)
            ->setHandles($handles)
            ->buildPropertyString();

        return $this->newPanel()
            ->setHeaderText(\Yii::t("app",'Subscribers'))
            ->setOrder(20000)
            ->appendChild(new PhutilSafeHTML($susbscribers_view));
    }
}
