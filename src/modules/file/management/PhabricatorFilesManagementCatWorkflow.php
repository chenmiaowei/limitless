<?php

namespace orangins\modules\file\management;

use orangins\modules\file\exception\PhabricatorFileIntegrityException;
use orangins\modules\file\models\PhabricatorFile;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorFilesManagementCatWorkflow
 * @package orangins\modules\file\management
 * @author 陈妙威
 */
final class PhabricatorFilesManagementCatWorkflow
    extends PhabricatorFilesManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('cat')
            ->setSynopsis(pht('Print the contents of a file.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'begin',
                        'param' => 'bytes',
                        'help' => pht('Begin printing at a specific offset.'),
                    ),
                    array(
                        'name' => 'end',
                        'param' => 'bytes',
                        'help' => pht('End printing at a specific offset.'),
                    ),
                    array(
                        'name' => 'salvage',
                        'help' => pht(
                            'DANGEROUS. Attempt to salvage file content even if the ' .
                            'integrity check fails. If an adversary has tampered with ' .
                            'the file, the content may be unsafe.'),
                    ),
                    array(
                        'name' => 'names',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $names = $args->getArg('names');
        if (count($names) > 1) {
            throw new PhutilArgumentUsageException(
                pht('Specify exactly one file to print, like "%s".', 'F123'));
        } else if (!$names) {
            throw new PhutilArgumentUsageException(
                pht('Specify a file to print, like "%s".', 'F123'));
        }

        /** @var PhabricatorFile $file */
        $file = head($this->loadFilesWithNames($names));

        $begin = $args->getArg('begin');
        $end = $args->getArg('end');

        $file->makeEphemeral();

        // If we're running in "salvage" mode, wipe out any integrity hash which
        // may be present. This makes us read file data without performing an
        // integrity check.
        $salvage = $args->getArg('salvage');
        if ($salvage) {
            $file->setIntegrityHash(null);
        }

        try {
            $iterator = $file->getFileDataIterator($begin, $end);
            foreach ($iterator as $data) {
                echo $data;
            }
        } catch (PhabricatorFileIntegrityException $ex) {
            throw new PhutilArgumentUsageException(
                pht(
                    'File data integrity check failed. Use "--salvage" to bypass ' .
                    'integrity checks. This flag is dangerous, use it at your own ' .
                    'risk. Underlying error: %s',
                    $ex->getMessage()));
        }

        return 0;
    }

}
