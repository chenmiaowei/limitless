<?php

namespace orangins\modules\file\engine;

use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\util\PhabricatorHash;
use PhutilMethodNotImplementedException;
use orangins\modules\file\format\PhabricatorFileStorageFormat;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileChunk;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use Exception;

/**
 * Class PhabricatorChunkedFileStorageEngine
 * @package orangins\modules\file\engine
 * @author 陈妙威
 */
final class PhabricatorChunkedFileStorageEngine
    extends PhabricatorFileStorageEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineIdentifier()
    {
        return 'chunks';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getEnginePriority()
    {
        return 60000;
    }

    /**
     * We can write chunks if we have at least one valid storage engine
     * underneath us.
     */
    public function canWriteFiles()
    {
        return (bool)$this->getWritableEngine();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasFilesizeLimit()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isChunkEngine()
    {
        return true;
    }

    /**
     * @param $data
     * @param array $params
     * @return string|void
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function writeFile($data, array $params)
    {
        // The chunk engine does not support direct writes.
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $handle
     * @return string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function readFile($handle)
    {
        // This is inefficient, but makes the API work as expected.
        $chunks = $this->loadAllChunks($handle, true);

        $buffer = '';
        foreach ($chunks as $chunk) {
            $data_file = $chunk->getDataFile();
            if (!$data_file) {
                throw new Exception(\Yii::t("app",'This file data is incomplete!'));
            }

            $buffer .= $chunk->getDataFile()->loadFileData();
        }

        return $buffer;
    }

    /**
     * @param $handle
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function deleteFile($handle)
    {
        $engine = new PhabricatorDestructionEngine();
        $chunks = $this->loadAllChunks($handle, true);
        foreach ($chunks as $chunk) {
            $engine->destroyObject($chunk);
        }
    }

    /**
     * @param $handle
     * @param $need_files
     * @return PhabricatorFileChunk[]
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function loadAllChunks($handle, $need_files)
    {
        $chunks = PhabricatorFileChunk::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withChunkHandles(array($handle))
            ->needDataFiles($need_files)
            ->execute();
        $chunks = msort($chunks, 'getByteStart');

        return $chunks;
    }

    /**
     * Compute a chunked file hash for the viewer.
     *
     * We can not currently compute a real hash for chunked file uploads (because
     * no process sees all of the file data).
     *
     * We also can not trust the hash that the user claims to have computed. If
     * we trust the user, they can upload some `evil.exe` and claim it has the
     * same file hash as `good.exe`. When another user later uploads the real
     * `good.exe`, we'll just create a reference to the existing `evil.exe`. Users
     * who download `good.exe` will then receive `evil.exe`.
     *
     * Instead, we rehash the user's claimed hash with account secrets. This
     * allows users to resume file uploads, but not collide with other users.
     *
     * Ideally, we'd like to be able to verify hashes, but this is complicated
     * and time consuming and gives us a fairly small benefit.
     *
     * @param PhabricatorUser $viewer
     * @param PhabricatorUser Viewing user.
     * @return string Rehashed file hash.
     * @throws Exception
     */
    public static function getChunkedHash(PhabricatorUser $viewer, $hash)
    {
        if (!$viewer->getPHID()) {
            throw new Exception(
                \Yii::t("app",'Unable to compute chunked hash without real viewer!'));
        }

        $input = $viewer->getAccountSecret() . ':' . $hash . ':' . $viewer->getPHID();
        return self::getChunkedHashForInput($input);
    }

    /**
     * @param $input
     * @return string
     * @author 陈妙威
     * @throws Exception
     */
    public static function getChunkedHashForInput($input)
    {
        $rehash = PhabricatorHash::weakDigest($input);

        // Add a suffix to identify this as a chunk hash.
        $rehash = substr($rehash, 0, -2) . '-C';

        return $rehash;
    }

    /**
     * @param $length
     * @param array $properties
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \yii\base\Exception
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function allocateChunks($length, array $properties)
    {
        $file = PhabricatorFile::newChunkedFile($this, $length, $properties);

        $chunk_size = $this->getChunkSize();

        $handle = $file->getStorageHandle();

        /** @var PhabricatorFileChunk[] $chunks */
        $chunks = array();
        for ($ii = 0; $ii < $length; $ii += $chunk_size) {
            $chunks[] = PhabricatorFileChunk::initializeNewChunk(
                $handle,
                $ii,
                min($ii + $chunk_size, $length));
        }

        $file->openTransaction();
        foreach ($chunks as $chunk) {
            if (!$chunk->save()) {
                throw new ActiveRecordException("File chunk save error. ", $chunk->getErrorSummary(true));
            }
        }
        $file->saveAndIndex();
        $file->saveTransaction();

        return $file;
    }

    /**
     * Find a storage engine which is suitable for storing chunks.
     *
     * This engine must be a writable engine, have a filesize limit larger than
     * the chunk limit, and must not be a chunk engine itself.
     */
    private function getWritableEngine()
    {
        // NOTE: We can't just load writable engines or we'll loop forever.
        $engines = parent::loadAllEngines();

        foreach ($engines as $engine) {
            if ($engine->isChunkEngine()) {
                continue;
            }

            if ($engine->isTestEngine()) {
                continue;
            }

            if (!$engine->canWriteFiles()) {
                continue;
            }

            if ($engine->hasFilesizeLimit()) {
                if ($engine->getFilesizeLimit() < $this->getChunkSize()) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getChunkSize()
    {
        return (4 * 1024 * 1024);
    }

    /**
     * @param PhabricatorFile $file
     * @param $begin
     * @param $end
     * @param PhabricatorFileStorageFormat $format
     * @return array|PhabricatorFileChunkIterator
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getRawFileDataIterator(
        PhabricatorFile $file,
        $begin,
        $end,
        PhabricatorFileStorageFormat $format)
    {

        // NOTE: It is currently impossible for files stored with the chunk
        // engine to have their own formatting (instead, the individual chunks
        // are formatted), so we ignore the format object.

        /** @var PhabricatorFileChunk[] $chunks */
        $chunks = PhabricatorFileChunk::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withChunkHandles(array($file->getStorageHandle()))
            ->withByteRange($begin, $end)
            ->needDataFiles(true)
            ->execute();
        $chunks = msort($chunks, 'getByteStart');
        return new PhabricatorFileChunkIterator($chunks, $begin, $end);
    }
}
