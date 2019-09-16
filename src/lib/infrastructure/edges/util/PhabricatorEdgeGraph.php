<?php

namespace orangins\lib\infrastructure\edges\util;

use AbstractDirectedGraph;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use Exception;

/**
 * Class PhabricatorEdgeGraph
 * @package orangins\lib\infrastructure\edges\util
 * @author 陈妙威
 */
final class PhabricatorEdgeGraph extends AbstractDirectedGraph
{

    /**
     * @var
     */
    private $edgeType;

    /**
     * @param $edge_type
     * @return $this
     * @author 陈妙威
     */
    public function setEdgeType($edge_type)
    {
        $this->edgeType = $edge_type;
        return $this;
    }

    /**
     * @param array $nodes
     * @return array
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function loadEdges(array $nodes)
    {
        if (!$this->edgeType) {
            throw new Exception(\Yii::t("app",'Set edge type before loading graph!'));
        }

        $edges = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs($nodes)
            ->withEdgeTypes(array($this->edgeType))
            ->execute();

        $results = array_fill_keys($nodes, array());
        foreach ($edges as $src => $types) {
            foreach ($types as $type => $dsts) {
                foreach ($dsts as $dst => $edge) {
                    $results[$src][] = $dst;
                }
            }
        }

        return $results;
    }

}
