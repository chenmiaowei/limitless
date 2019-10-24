<?php

namespace orangins\modules\file\management;

use Exception;
use orangins\modules\file\models\PhabricatorFile;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorFilesManagementCompactWorkflow
 * @package orangins\modules\file\management
 * @author 陈妙威
 */
final class PhabricatorFilesManagementCompactWorkflow
    extends PhabricatorFilesManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('compact')
            ->setSynopsis(
                pht(
                    'Merge identical files to share the same storage. In some cases, ' .
                    'this can repair files with missing data.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'dry-run',
                        'help' => pht('Show what would be compacted.'),
                    ),
                    array(
                        'name' => 'all',
                        'help' => pht('Compact all files.'),
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
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $iterator = $this->buildIterator($args);
        if (!$iterator) {
            throw new PhutilArgumentUsageException(
                pht(
                    'Either specify a list of files to compact, or use `%s` ' .
                    'to compact all files.',
                    '--all'));
        }

        $is_dry_run = $args->getArg('dry-run');

        foreach ($iterator as $file) {
            $monogram = $file->getMonogram();

            $hash = $file->getContentHash();
            if (!$hash) {
                $console->writeOut(
                    "%s\n",
                    pht('%s: No content hash.', $monogram));
                continue;
            }

            // Find other files with the same content hash. We're going to point
            // them at the data for this file.
            /** @var PhabricatorFile[] $similar_files */
            $similar_files = id(new PhabricatorFile())->loadAllWhere(
                'contentHash = %s AND id != %d AND
          (storageEngine != %s OR storageHandle != %s)',
                $hash,
                $file->getID(),
                $file->getStorageEngine(),
                $file->getStorageHandle());

            $similar_files = PhabricatorFile::find()
                ->andWhere(
                    'content_hash=:content_hash and id!=:id and (storage_engine!=:storage_engine or storage_handle!=:storage_handle)'
                , [
                    ':content_hash' => $hash,
                    ':id' => $file->getID(),
                    ':storage_engine' => $file->getStorageEngine(),
                    ':storage_handle' => $file->getStorageHandle(),
                ])->all();
            if (!$similar_files) {
                $console->writeOut(
                    "%s\n",
                    pht('%s: No other files with the same content hash.', $monogram));
                continue;
            }

            // Only compact files into this one if we can load the data. This
            // prevents us from breaking working files if we're missing some data.
            try {
                $data = $file->loadFileData();
            } catch (Exception $ex) {
                $data = null;
            }

            if ($data === null) {
                $console->writeOut(
                    "%s\n",
                    pht(
                        '%s: Unable to load file data; declining to compact.',
                        $monogram));
                continue;
            }

            foreach ($similar_files as $similar_file) {
                if ($is_dry_run) {
                    $console->writeOut(
                        "%s\n",
                        pht(
                            '%s: Would compact storage with %s.',
                            $monogram,
                            $similar_file->getMonogram()));
                    continue;
                }

                $console->writeOut(
                    "%s\n",
                    pht(
                        '%s: Compacting storage with %s.',
                        $monogram,
                        $similar_file->getMonogram()));

                $old_instance = null;
                try {
                    $old_instance = $similar_file->instantiateStorageEngine();
                    $old_engine = $similar_file->getStorageEngine();
                    $old_handle = $similar_file->getStorageHandle();
                } catch (Exception $ex) {
                    // If the old stuff is busted, we just won't try to delete the
                    // old data.
                    phlog($ex);
                }

                $similar_file
                    ->setStorageEngine($file->getStorageEngine())
                    ->setStorageHandle($file->getStorageHandle())
                    ->save();

                if ($old_instance) {
                    $similar_file->deleteFileDataIfUnused(
                        $old_instance,
                        $old_engine,
                        $old_handle);
                }
            }
        }

        return 0;
    }

}
