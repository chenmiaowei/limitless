<?php

namespace orangins\modules\subscriptions\policyrule;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use orangins\modules\transactions\edges\PhabricatorSubscribedToObjectEdgeType;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSubscriptionsSubscribersPolicyRule
 * @package orangins\modules\subscriptions\policyrule
 * @author 陈妙威
 */
final class PhabricatorSubscriptionsSubscribersPolicyRule extends PhabricatorPolicyRule
{

    /**
     * @var array
     */
    private $subscribed = array();
    /**
     * @var array
     */
    private $sourcePHIDs = array();

    /**
     * @return string
     * @author 陈妙威
     */
    public function getObjectPolicyKey()
    {
        return 'subscriptions.subscribers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getObjectPolicyName()
    {
        return \Yii::t("app", 'Subscribers');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPolicyExplanation()
    {
        return \Yii::t("app", 'Subscribers can take this action.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getRuleDescription()
    {
        return \Yii::t("app", 'subscribers');
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @return bool
     * @author 陈妙威
     */
    public function canApplyToObject(PhabricatorPolicyInterface $object)
    {
        return ($object instanceof PhabricatorSubscribableInterface);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $values
     * @param array $objects
     * @throws \ReflectionException

     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function willApplyRules(
        PhabricatorUser $viewer,
        array $values,
        array $objects)
    {

        // We want to let the user see the object if they're a subscriber or
        // a member of any project which is a subscriber. Additionally, because
        // subscriber state is complex, we need to read hints passed from
        // the TransactionEditor to predict policy state after transactions apply.

        $viewer_phid = $viewer->getPHID();
        if (!$viewer_phid) {
            return;
        }

        if (empty($this->subscribed[$viewer_phid])) {
            $this->subscribed[$viewer_phid] = array();
        }

        // Load the project PHIDs the user is a member of. We use the omnipotent
        // user here because projects may themselves have "Subscribers" visibility
        // policies and we don't want to get stuck in an infinite stack of
        // recursive policy checks. See T13106.
        if (!isset($this->sourcePHIDs[$viewer_phid])) {
//            $projects = (new PhabricatorProjectQuery())
//                ->setViewer(PhabricatorUser::getOmnipotentUser())
//                ->withMemberPHIDs(array($viewer_phid))
//                ->execute();
//
//            $source_phids = mpull($projects, 'getPHID');

            $source_phids[] = $viewer_phid;
            $this->sourcePHIDs[$viewer_phid] = $source_phids;
        }

        // Look for transaction hints.
        foreach ($objects as $key => $object) {
            $cache = $this->getTransactionHint($object);
            if ($cache === null) {
                // We don't have a hint for this object, so we'll deal with it below.
                continue;
            }

            // We have a hint, so use that as the source of truth.
            unset($objects[$key]);

            foreach ($this->sourcePHIDs[$viewer_phid] as $source_phid) {
                if (isset($cache[$source_phid])) {
                    $this->subscribed[$viewer_phid][$object->getPHID()] = true;
                    break;
                }
            }
        }

        $phids = mpull($objects, 'getPHID');
        if (!$phids) {
            return;
        }

        $edge_query = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs($this->sourcePHIDs[$viewer_phid])
            ->withEdgeTypes(
                array(
                    PhabricatorSubscribedToObjectEdgeType::EDGECONST,
                ))
            ->withDestinationPHIDs($phids);

        $edge_query->execute();

        $subscribed = $edge_query->getDestinationPHIDs();
        if (!$subscribed) {
            return;
        }

        $this->subscribed[$viewer_phid] += array_fill_keys($subscribed, true);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $value
     * @param PhabricatorPolicyInterface|PhabricatorSubscribableInterface $object
     * @return bool|mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function applyRule(
        PhabricatorUser $viewer,
        $value,
        PhabricatorPolicyInterface $object)
    {

        $viewer_phid = $viewer->getPHID();
        if (!$viewer_phid) {
            return false;
        }

        if ($object->isAutomaticallySubscribed($viewer_phid)) {
            return true;
        }

        $subscribed = ArrayHelper::getValue($this->subscribed, $viewer_phid);
        return isset($subscribed[$object->getPHID()]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getValueControlType()
    {
        return self::CONTROL_TYPE_NONE;
    }

}
