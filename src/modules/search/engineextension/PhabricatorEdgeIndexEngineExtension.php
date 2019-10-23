<?php

namespace orangins\modules\search\engineextension;

use Exception;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\search\index\PhabricatorIndexEngine;
use orangins\modules\search\index\PhabricatorIndexEngineExtension;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorEdgeIndexEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorEdgeIndexEngineExtension
    extends PhabricatorIndexEngineExtension
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getIndexEdgeType();

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getIndexDestinationPHIDs($object);

    /**
     * @param PhabricatorIndexEngine $engine
     * @param ActiveRecordPHID $object
     * @return mixed
     * @throws PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    final public function indexObject(
        PhabricatorIndexEngine $engine,
        $object)
    {

        $edge_type = $this->getIndexEdgeType();

        $old_edges = PhabricatorEdgeQuery::loadDestinationPHIDs(
            $object->getPHID(),
            $edge_type);
        $old_edges = array_fuse($old_edges);

        $new_edges = $this->getIndexDestinationPHIDs($object);
        $new_edges = array_fuse($new_edges);

        $add_edges = array_diff_key($new_edges, $old_edges);
        $rem_edges = array_diff_key($old_edges, $new_edges);

        if (!$add_edges && !$rem_edges) {
            return;
        }

        $editor = new PhabricatorEdgeEditor();

        foreach ($add_edges as $phid) {
            $editor->addEdge($object->getPHID(), $edge_type, $phid);
        }

        foreach ($rem_edges as $phid) {
            $editor->removeEdge($object->getPHID(), $edge_type, $phid);
        }

        $editor->save();
    }

    /**
     * @param $object
     * @return |null
     * @author 陈妙威
     */
    final public function getIndexVersion($object)
    {
        $phids = $this->getIndexDestinationPHIDs($object);
        sort($phids);
        $phids = implode(':', $phids);
        return PhabricatorHash::digestForIndex($phids);
    }

}
