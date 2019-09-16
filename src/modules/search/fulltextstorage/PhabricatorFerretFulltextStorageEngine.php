<?php

namespace orangins\modules\search\fulltextstorage;

use orangins\lib\infrastructure\cluster\search\PhabricatorMySQLSearchHost;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\search\ferret\PhabricatorFerretEngine;
use orangins\modules\search\ferret\PhabricatorFerretInterface;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;
use orangins\modules\search\models\PhabricatorSavedQuery;
use PhutilClassMapQuery;

/**
 * Class PhabricatorFerretFulltextStorageEngine
 * @package orangins\modules\search\fulltextstorage
 * @author 陈妙威
 */
final class PhabricatorFerretFulltextStorageEngine
    extends PhabricatorFulltextStorageEngine
{

    /**
     * @var array
     */
    private $fulltextTokens = array();
    /**
     * @var
     */
    private $engineLimits;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineIdentifier()
    {
        return 'mysql';
    }

    /**
     * @return PhabricatorMySQLSearchHost|\orangins\lib\infrastructure\cluster\search\PhabricatorSearchHost
     * @author 陈妙威
     */
    public function getHostType()
    {
        return new PhabricatorMySQLSearchHost($this);
    }

    /**
     * @param PhabricatorSearchAbstractDocument $doc
     * @author 陈妙威
     */
    public function reindexAbstractDocument(
        PhabricatorSearchAbstractDocument $doc)
    {

        // NOTE: The Ferret engine indexes are rebuilt by an extension rather than
        // by the main fulltext engine, and are always built regardless of
        // configuration.

        return;
    }

    /**
     * @param PhabricatorSavedQuery $query
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function executeSearch(PhabricatorSavedQuery $query)
    {
        /** @var PhabricatorFerretInterface[] $all_objects */
        $all_objects = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorFerretInterface::class)
            ->execute();

        $type_map = array();
        foreach ($all_objects as $object) {
            $phid_type = PhabricatorPHID::phid_get_type($object->generatePHID());

            $type_map[$phid_type] = array(
                'object' => $object,
                'engine' => $object->newFerretEngine(),
            );
        }

        $types = $query->getParameter('types');
        if ($types) {
            $type_map = array_select_keys($type_map, $types);
        }

        $offset = (int)$query->getParameter('offset', 0);
        $limit = (int)$query->getParameter('limit', 25);

        // NOTE: For now, it's okay to query with the omnipotent viewer here
        // because we're just returning PHIDs which we'll filter later.
        $viewer = PhabricatorUser::getOmnipotentUser();

        $type_results = array();
        $metadata = array();
        foreach ($type_map as $type => $spec) {
            /** @var PhabricatorFerretEngine $engine */
            $engine = $spec['engine'];
            $object = $spec['object'];

            $local_query = new PhabricatorSavedQuery();
            $local_query->setParameter('query', $query->getParameter('query'));

            $project_phids = $query->getParameter('projectPHIDs');
            if ($project_phids) {
                $local_query->setParameter('projectPHIDs', $project_phids);
            }

            $subscriber_phids = $query->getParameter('subscriberPHIDs');
            if ($subscriber_phids) {
                $local_query->setParameter('subscriberPHIDs', $subscriber_phids);
            }

            $search_engine = $engine->newSearchEngine();
            $search_engine->setViewer($viewer);

            /** @var PhabricatorCursorPagedPolicyAwareQuery $engine_query */
            $engine_query = $search_engine->buildQueryFromSavedQuery($local_query)
                ->setViewer($viewer);

            $engine_query
                ->withFerretQuery($engine, $query)
                ->setOrder('relevance')
                ->setLimit($offset + $limit);

            $results = $engine_query->execute();
            $results = mpull($results, null, 'getPHID');
            $type_results[$type] = $results;

            $metadata += $engine_query->getFerretMetadata();

            if (!$this->fulltextTokens) {
                $this->fulltextTokens = $engine_query->getFerretTokens();
            }
        }

        $list = array();
        foreach ($type_results as $type => $results) {
            $list += $results;
        }

        // Currently, the list is grouped by object type. For example, all the
        // tasks might be first, then all the revisions, and so on. In each group,
        // the results are ordered properly.

        // Reorder the results so that the highest-ranking results come first,
        // no matter which object types they belong to.

        $metadata = msortv($metadata, 'getRelevanceSortVector');
        $list = array_select_keys($list, array_keys($metadata)) + $list;

        $result_slice = array_slice($list, $offset, $limit, true);
        return array_keys($result_slice);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function indexExists()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIndexStats()
    {
        return false;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFulltextTokens()
    {
        return $this->fulltextTokens;
    }
}
