<?php

namespace orangins\modules\search\management;

use orangins\lib\infrastructure\cluster\search\PhabricatorSearchService;
use PhutilArgumentParser;

/**
 * Class PhabricatorSearchManagementInitWorkflow
 * @package orangins\modules\search\management
 * @author 陈妙威
 */
final class PhabricatorSearchManagementInitWorkflow
    extends PhabricatorSearchManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('init')
            ->setSynopsis(pht('Initialize or repair a search service.'))
            ->setExamples('**init**');
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentUsageException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $this->validateClusterSearchConfig();

        $work_done = false;
        foreach (PhabricatorSearchService::getAllServices() as $service) {
            echo tsprintf(
                "%s\n",
                pht(
                    'Initializing search service "%s".',
                    $service->getDisplayName()));

            if (!$service->isWritable()) {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Skipping service "%s" because it is not writable.',
                        $service->getDisplayName()));
                continue;
            }

            $engine = $service->getEngine();

            if (!$engine->indexExists()) {
                echo tsprintf(
                    "%s\n",
                    pht('Service index does not exist, creating...'));

                $engine->initIndex();
                $work_done = true;
            } else if (!$engine->indexIsSane()) {
                echo tsprintf(
                    "%s\n",
                    pht('Service index is out of date, repairing...'));

                $engine->initIndex();
                $work_done = true;
            } else {
                echo tsprintf(
                    "%s\n",
                    pht('Service index is already up to date.'));
            }

            echo tsprintf(
                "%s\n",
                pht('Done.'));
        }

        if (!$work_done) {
            echo tsprintf(
                "%s\n",
                pht('No services need initialization.'));
            return 0;
        }

        echo tsprintf(
            "%s\n",
            pht('Service initialization complete.'));
    }
}
