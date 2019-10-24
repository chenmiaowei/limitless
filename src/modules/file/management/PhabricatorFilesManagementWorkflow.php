<?php

namespace orangins\modules\file\management;

use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use orangins\lib\infrastructure\storage\lisk\LiskMigrationIterator;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorFilesManagementWorkflow
 * @package orangins\modules\file\management
 * @author 陈妙威
 */
abstract class PhabricatorFilesManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @param PhutilArgumentParser $args
     * @return array|LiskMigrationIterator|null
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @author 陈妙威
     */
    protected function buildIterator(PhutilArgumentParser $args)
    {
        $names = $args->getArg('names');

        if ($args->getArg('all')) {
            if ($names) {
                throw new PhutilArgumentUsageException(
                    pht(
                        'Specify either a list of files or `%s`, but not both.',
                        '--all'));
            }
            return new LiskMigrationIterator(new PhabricatorFile());
        }

        if ($names) {
            return $this->loadFilesWithNames($names);
        }

        return null;
    }

    /**
     * @param array $names
     * @return array
     * @throws PhutilArgumentUsageException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function loadFilesWithNames(array $names)
    {
        $query = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->withNames($names)
            ->withTypes(array(PhabricatorFileFilePHIDType::TYPECONST));

        $query->execute();
        $files = $query->getNamedResults();

        foreach ($names as $name) {
            if (empty($files[$name])) {
                throw new PhutilArgumentUsageException(
                    pht(
                        "No file '%s' exists!",
                        $name));
            }
        }

        return array_values($files);
    }

}
