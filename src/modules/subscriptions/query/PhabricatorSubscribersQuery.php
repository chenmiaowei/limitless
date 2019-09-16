<?php

namespace orangins\modules\subscriptions\query;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\infrastructure\query\PhabricatorBaseQuery;
use orangins\modules\transactions\edges\PhabricatorObjectHasSubscriberEdgeType;

/**
 * Class PhabricatorSubscribersQuery
 * @package orangins\modules\subscriptions\query
 * @author 陈妙威
 */
final class PhabricatorSubscribersQuery extends PhabricatorBaseQuery
{
    /**
     * @var
     */
    private $objectPHIDs;
    /**
     * @var
     */
    private $subscriberPHIDs;

    /**
     * @param $phid
     * @return array

     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public static function loadSubscribersForPHID($phid)
    {
        if (!$phid) {
            return array();
        }
        $subscribers = (new PhabricatorSubscribersQuery())
            ->withObjectPHIDs(array($phid))
            ->execute();
        return $subscribers[$phid];
    }

    /**
     * @param array $object_phids
     * @return $this
     * @author 陈妙威
     */
    public function withObjectPHIDs(array $object_phids)
    {
        $this->objectPHIDs = $object_phids;
        return $this;
    }

    /**
     * @param array $subscriber_phids
     * @return $this
     * @author 陈妙威
     */
    public function withSubscriberPHIDs(array $subscriber_phids)
    {
        $this->subscriberPHIDs = $subscriber_phids;
        return $this;
    }

    /**
     * @return array

     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function execute()
    {
        $query = new PhabricatorEdgeQuery();

        $edge_type = PhabricatorObjectHasSubscriberEdgeType::EDGECONST;

        $query->withSourcePHIDs($this->objectPHIDs);
        $query->withEdgeTypes(array($edge_type));

        if ($this->subscriberPHIDs) {
            $query->withDestinationPHIDs($this->subscriberPHIDs);
        }

        $edges = $query->execute();

        $results = array_fill_keys($this->objectPHIDs, array());
        foreach ($edges as $src => $edge_types) {
            foreach ($edge_types[$edge_type] as $dst => $data) {
                $results[$src][] = $dst;
            }
        }

        return $results;
    }
}
