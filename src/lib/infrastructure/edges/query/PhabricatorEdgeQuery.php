<?php

namespace orangins\lib\infrastructure\edges\query;

use orangins\lib\infrastructure\edges\constants\PhabricatorEdgeConfig;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\infrastructure\query\PhabricatorBaseQuery;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use PhutilInvalidStateException;
use orangins\modules\phid\helpers\PhabricatorPHID;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Load object edges created by @{class:PhabricatorEdgeEditor}.
 *
 *   name=Querying Edges
 *   $src  = $earth_phid;
 *   $type = PhabricatorEdgeConfig::TYPE_BODY_HAS_SATELLITE;
 *
 *   // Load the earth's satellites.
 *   $satellite_edges = (new PhabricatorEdgeQuery())
 *     ->withSourcePHIDs(array($src))
 *     ->withEdgeTypes(array($type))
 *     ->execute();
 *
 * For more information on edges, see @{article:Using Edges}.
 *
 * @task config   Configuring the Query
 * @task exec     Executing the Query
 * @task internal Internal
 */
final class PhabricatorEdgeQuery extends PhabricatorBaseQuery
{

    /**
     * @var
     */
    private $sourcePHIDs;
    /**
     * @var
     */
    private $destPHIDs;
    /**
     * @var
     */
    private $edgeTypes;
    /**
     * @var
     */
    private $resultSet;

    /**
     *
     */
    const ORDER_OLDEST_FIRST = 'order:oldest';
    /**
     *
     */
    const ORDER_NEWEST_FIRST = 'order:newest';
    /**
     * @var string
     */
    private $order = self::ORDER_NEWEST_FIRST;

    /**
     * @var
     */
    private $needEdgeData;


    /* -(  Configuring the Query  )---------------------------------------------- */


    /**
     * Find edges originating at one or more source PHIDs. You MUST provide this
     * to execute an edge query.
     *
     * @param array List of source PHIDs.
     * @return static
     *
     * @task config
     */
    public function withSourcePHIDs(array $source_phids)
    {
        $this->sourcePHIDs = $source_phids;
        return $this;
    }


    /**
     * Find edges terminating at one or more destination PHIDs.
     *
     * @param array List of destination PHIDs.phid_group_by_type
     * @return static
     *
     */
    public function withDestinationPHIDs(array $dest_phids)
    {
        $this->destPHIDs = $dest_phids;
        return $this;
    }


    /**
     * Find edges of specific types.
     *
     * @param array List of PhabricatorEdgeConfig type constants.
     * @return static
     *
     * @task config
     */
    public function withEdgeTypes(array $types)
    {
        $this->edgeTypes = $types;
        return $this;
    }


    /**
     * Configure the order edge results are returned in.
     *
     * @param string Order constant.
     * @return static
     *
     * @task config
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }


    /**
     * When loading edges, also load edge data.
     *
     * @param bool True to load edge data.
     * @return static
     *
     * @task config
     */
    public function needEdgeData($need)
    {
        $this->needEdgeData = $need;
        return $this;
    }


    /* -(  Executing the Query  )------------------------------------------------ */


    /**
     * Convenience method for loading destination PHIDs with one source and one
     * edge type. Equivalent to building a full query, but simplifies a common
     * use case.
     *
     * @param $src_phid
     * @param $edge_type
     * @return array<phid> List of destination PHIDs.
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     */
    public static function loadDestinationPHIDs($src_phid, $edge_type)
    {
        $edges = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs(array($src_phid))
            ->withEdgeTypes(array($edge_type))
            ->execute();
        return array_keys($edges[$src_phid][$edge_type]);
    }

    /**
     * Convenience method for loading a single edge's metadata for
     * a given source, destination, and edge type. Returns null
     * if the edge does not exist or does not have metadata. Builds
     * and immediately executes a full query.
     *
     * @param $src_phid
     * @param $edge_type
     * @param $dest_phid
     * @return array Edge annotation (or null).
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     */
    public static function loadSingleEdgeData($src_phid, $edge_type, $dest_phid)
    {
        $edges = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs(array($src_phid))
            ->withEdgeTypes(array($edge_type))
            ->withDestinationPHIDs(array($dest_phid))
            ->needEdgeData(true)
            ->execute();

        if (isset($edges[$src_phid][$edge_type][$dest_phid]['data'])) {
            return $edges[$src_phid][$edge_type][$dest_phid]['data'];
        }
        return null;
    }


    /**
     * Load specified edges.
     *
     * @task exec
     * @return array
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     */
    public function execute()
    {
        if (!$this->sourcePHIDs) {
            throw new Exception(
                \Yii::t("app",
                    'You must use {0} to query edges.',
                    [
                        'withSourcePHIDs()'
                    ]));
        }

        $sources = PhabricatorPHID::phid_group_by_type($this->sourcePHIDs);

        $result = array();

        // When a query specifies types, make sure we return data for all queried
        // types.
        if ($this->edgeTypes) {
            foreach ($this->sourcePHIDs as $phid) {
                foreach ($this->edgeTypes as $type) {
                    $result[$phid][$type] = array();
                }
            }
        }

        foreach ($sources as $type => $phids) {
            $this->buildWhereClause();
            $this->buildOrderClause();

            $buildQuery = PhabricatorEdgeConfig::buildObject($type);
            assert_instances_of([$buildQuery], PhabricatorEdgeInterface::class);

            $this->buildOrderClause();
            $this->buildWhereClause();
            $edges = $this
                ->select(['src', 'type', 'dst', 'seq', 'data_id', 'created_at', 'updated_at'])
                ->from($buildQuery->edgeBaseTableName() . "_" . PhabricatorEdgeConfig::TABLE_NAME_EDGE)
                ->all();

            if ($this->needEdgeData) {
                $data_ids = array_filter(ipull($edges, 'data_id'));
                $data_map = array();
                if ($data_ids) {
                    $data_rows = (new PhabricatorQuery())
                        ->from($buildQuery->edgeBaseTableName() . "_" . PhabricatorEdgeConfig::TABLE_NAME_EDGEDATA)
                        ->andWhere(['IN', 'id', $data_ids])
                        ->all();
                    foreach ($data_rows as $row) {
                        $data_map[$row['id']] = ArrayHelper::getValue(
                            phutil_json_decode($row['data']),
                            'data');
                    }
                }
                foreach ($edges as $key => $edge) {
                    $edges[$key]['data'] = ArrayHelper::getValue($data_map, $edge['data_id'], array());
                }
            }

            foreach ($edges as $edge) {
                $result[$edge['src']][$edge['type']][$edge['dst']] = $edge;
            }
        }

        $this->resultSet = $result;
        return $result;
    }


    /**
     * Convenience function for selecting edge destination PHIDs after calling
     * execute().
     *
     * Returns a flat list of PHIDs matching the provided source PHID and type
     * filters. By default, the filters are empty so all PHIDs will be returned.
     * For example, if you're doing a batch query from several sources, you might
     * write code like this:
     *
     *   $query = new PhabricatorEdgeQuery();
     *   $query->setViewer($viewer);
     *   $query->withSourcePHIDs(mpull($objects, 'getPHID'));
     *   $query->withEdgeTypes(array($some_type));
     *   $query->execute();
     *
     *   // Gets all of the destinations.
     *   $all_phids = $query->getDestinationPHIDs();
     *   $handles = (new PhabricatorHandleQuery())
     *     ->setViewer($viewer)
     *     ->withPHIDs($all_phids)
     *     ->execute();
     *
     *   foreach ($objects as $object) {
     *     // Get all of the destinations for the given object.
     *     $dst_phids = $query->getDestinationPHIDs(array($object->getPHID()));
     *     $object->attachHandles(array_select_keys($handles, $dst_phids));
     *   }
     *
     * @param array? List of PHIDs to select, or empty to select all.
     * @param array? List of edge types to select, or empty to select all.
     * @return array<phid> List of matching destination PHIDs.
     * @throws PhutilInvalidStateException
     */
    public function getDestinationPHIDs(
        array $src_phids = array(),
        array $types = array())
    {
        if ($this->resultSet === null) {
            throw new PhutilInvalidStateException('execute');
        }

        $result_phids = array();

        $set = $this->resultSet;
        if ($src_phids) {
            $set = array_select_keys($set, $src_phids);
        }

        foreach ($set as $src => $edges_by_type) {
            if ($types) {
                $edges_by_type = array_select_keys($edges_by_type, $types);
            }

            foreach ($edges_by_type as $edges) {
                foreach ($edges as $edge_phid => $edge) {
                    $result_phids[$edge_phid] = true;
                }
            }
        }

        return array_keys($result_phids);
    }


    /* -(  Internals  )---------------------------------------------------------- */


    /**
     * @task internal
     */
    protected function buildWhereClause()
    {
        if ($this->sourcePHIDs) {
            $this->andWhere(['IN', 'src',  $this->sourcePHIDs]);
        }

        if ($this->edgeTypes) {
            $this->andWhere(['IN', 'type',  $this->edgeTypes]);
        }

        if ($this->destPHIDs) {
            // potentially complain if $this->edgeType was not set
            $this->andWhere(['IN', 'dst',  $this->destPHIDs]);
        }
    }


    /**
     * @task internal
     */
    private function buildOrderClause()
    {
        if ($this->order == self::ORDER_NEWEST_FIRST) {
            $this->orderBy('created_at DESC, seq DESC');
        } else {
            $this->orderBy('created_at ASC, seq ASC');
        }
    }
}