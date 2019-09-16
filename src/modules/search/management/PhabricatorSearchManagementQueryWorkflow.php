<?php

namespace orangins\modules\search\management;

use orangins\modules\search\query\PhabricatorSearchApplicationSearchEngine;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorSearchManagementQueryWorkflow
 * @package orangins\modules\search\management
 * @author 陈妙威
 */
final class PhabricatorSearchManagementQueryWorkflow
    extends PhabricatorSearchManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('query')
            ->setSynopsis(
                pht('Run a search query. Intended for debugging and development.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'query',
                        'param' => 'query',
                        'help' => pht('Raw query to execute.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \orangins\modules\typeahead\exception\PhabricatorTypeaheadInvalidTokenException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();
        $raw_query = $args->getArg('query');
        if (!strlen($raw_query)) {
            throw new PhutilArgumentUsageException(
                pht('Specify a query with --query.'));
        }

        $engine = (new PhabricatorSearchApplicationSearchEngine())
            ->setViewer($viewer);

        $saved = $engine->newSavedQuery();
        $saved->setParameter('query', $raw_query);

        $query = $engine->buildQueryFromSavedQuery($saved);
        $pager = $engine->newPagerForSavedQuery($saved);

        $results = $engine->executeQuery($query, $pager);
        if ($results) {
            foreach ($results as $result) {
                echo tsprintf(
                    "%s\t%s\n",
                    $result->getPHID(),
                    $result->getName());
            }
        } else {
            echo tsprintf(
                "%s\n",
                pht('No results.'));
        }

        return 0;
    }

}
