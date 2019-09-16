<?php
namespace orangins\modules\tag\query;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use orangins\modules\tag\edge\PhabricatorObjectHasTagEdgeType;

/**
 * @author 陈妙威
 */
final class PhabricatorTagsQuery extends PhabricatorQuery
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
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public static function loadTagsForPHID($phid)
    {
        if (!$phid) {
            return array();
        }
        $subscribers = (new PhabricatorTagsQuery())
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
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function execute()
    {
        $query = new PhabricatorEdgeQuery();

        $edge_type = PhabricatorObjectHasTagEdgeType::EDGECONST;

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
