<?php

namespace orangins\modules\subscriptions\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\editor\PhabricatorEditEngineExtension;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableDatasource;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\subscriptions\query\PhabricatorSubscribersQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorSubscribersEditField;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class PhabricatorSubscriptionsEditEngineExtension
 * @package orangins\modules\subscriptions\engineextension
 * @author 陈妙威
 */
final class PhabricatorSubscriptionsEditEngineExtension extends PhabricatorEditEngineExtension
{
    /**
     *
     */
    const EXTENSIONKEY = 'subscriptions.subscribers';
    /**
     *
     */
    const FIELDKEY = 'subscribers';

    /**
     *
     */
    const EDITKEY_ADD = 'subscribers.add';
    /**
     *
     */
    const EDITKEY_SET = 'subscribers.set';
    /**
     *
     */
    const EDITKEY_REMOVE = 'subscribers.remove';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionPriority()
    {
        return 750;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app", 'Subscriptions');
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {
        return ($object instanceof PhabricatorSubscribableInterface);
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildCustomEditFields(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {
        $subscribers_type = PhabricatorTransactions::TYPE_SUBSCRIBERS;

        $object_phid = $object->getPHID();
        if ($object_phid) {
            $sub_phids = PhabricatorSubscribersQuery::loadSubscribersForPHID($object_phid);
        } else {
            $sub_phids = array();
        }

        $viewer = $engine->getViewer();

        $subscribers_field = (new PhabricatorSubscribersEditField())
            ->setKey(self::FIELDKEY)
            ->setLabel(\Yii::t("app", 'Subscribers'))
            ->setEditTypeKey('subscribers')
            ->setAliases(array('subscriber', 'subscribers'))
            ->setIsCopyable(true)
            ->setUseEdgeTransactions(true)
            ->setCommentActionLabel(\Yii::t("app", 'Change Subscribers'))
            ->setCommentActionOrder(9000)
            ->setDescription(\Yii::t("app", 'Choose subscribers.'))
            ->setTransactionType($subscribers_type)
            ->setValue($sub_phids)
            ->setViewer($viewer);

        $subscriber_datasource = (new PhabricatorMetaMTAMailableDatasource())
            ->setViewer($viewer);

        $edit_add = $subscribers_field->getConduitEditType(self::EDITKEY_ADD)
            ->setConduitDescription(\Yii::t("app", 'Add subscribers.'));

        $edit_set = $subscribers_field->getConduitEditType(self::EDITKEY_SET)
            ->setConduitDescription(
                \Yii::t("app", 'Set subscribers, overwriting current value.'));

        $edit_rem = $subscribers_field->getConduitEditType(self::EDITKEY_REMOVE)
            ->setConduitDescription(\Yii::t("app", 'Remove subscribers.'));

        $subscribers_field->getBulkEditType(self::EDITKEY_ADD)
            ->setBulkEditLabel(\Yii::t("app", 'Add subscribers'))
            ->setDatasource($subscriber_datasource);

        $subscribers_field->getBulkEditType(self::EDITKEY_SET)
            ->setBulkEditLabel(\Yii::t("app", 'Set subscribers to'))
            ->setDatasource($subscriber_datasource);

        $subscribers_field->getBulkEditType(self::EDITKEY_REMOVE)
            ->setBulkEditLabel(\Yii::t("app", 'Remove subscribers'))
            ->setDatasource($subscriber_datasource);

        return array(
            $subscribers_field,
        );
    }

}
