<?php

namespace orangins\modules\file\management;

use Exception;
use orangins\modules\file\models\PhabricatorFile;
use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorFilesManagementEnginesWorkflow
 * @package orangins\modules\file\management
 * @author 陈妙威
 */
final class PhabricatorFilesManagementEnginesWorkflow
    extends PhabricatorFilesManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('engines')
            ->setSynopsis(pht('List available storage engines.'))
            ->setArguments(array());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $engines = PhabricatorFile::buildAllEngines();
        if (!$engines) {
            throw new Exception(pht('No storage engines are available.'));
        }

        foreach ($engines as $engine) {
            $console->writeOut(
                "%s\n",
                $engine->getEngineIdentifier());
        }

        return 0;
    }

}
