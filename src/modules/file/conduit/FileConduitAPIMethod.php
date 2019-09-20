<?php

namespace orangins\modules\file\conduit;

use Exception;
use orangins\lib\PhabricatorApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileChunk;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class FileConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
abstract class FileConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return null|\orangins\lib\PhabricatorApplication
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getApplication()
    {
        return PhabricatorApplication::getByClass(PhabricatorFilesApplication::class);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $file_phid
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadFileByPHID(PhabricatorUser $viewer, $file_phid)
    {
        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($file_phid))
            ->executeOne();
        if (!$file) {
            throw new Exception(pht('No such file "%s"!', $file_phid));
        }

        return $file;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorFile $file
     * @return PhabricatorFileChunk[]
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadFileChunks(
        PhabricatorUser $viewer,
        PhabricatorFile $file)
    {
        return $this
            ->newChunkQuery($viewer, $file)
            ->execute();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorFile $file
     * @param $start
     * @param $end
     * @return PhabricatorFileChunk
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadFileChunkForUpload(
        PhabricatorUser $viewer,
        PhabricatorFile $file,
        $start,
        $end)
    {

        $start = (int)$start;
        $end = (int)$end;

        $chunks = $this->newChunkQuery($viewer, $file)
            ->withByteRange($start, $end)
            ->execute();

        if (!$chunks) {
            throw new Exception(
                pht(
                    'There are no file data chunks in byte range %d - %d.',
                    $start,
                    $end));
        }

        if (count($chunks) !== 1) {
            phlog($chunks);
            throw new Exception(
                pht(
                    'There are multiple chunks in byte range %d - %d.',
                    $start,
                    $end));
        }

        /** @var PhabricatorFileChunk $chunk */
        $chunk = head($chunks);
        if ($chunk->getByteStart() != $start) {
            throw new Exception(
                pht(
                    'Chunk start byte is %d, not %d.',
                    $chunk->getByteStart(),
                    $start));
        }

        if ($chunk->getByteEnd() != $end) {
            throw new Exception(
                pht(
                    'Chunk end byte is %d, not %d.',
                    $chunk->getByteEnd(),
                    $end));
        }

        if ($chunk->getDataFilePHID()) {
            throw new Exception(
                pht('Chunk has already been uploaded.'));
        }

        return $chunk;
    }

    /**
     * @param $data
     * @return bool|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function decodeBase64($data)
    {
        $data = base64_decode($data, $strict = true);
        if ($data === false) {
            throw new Exception(pht('Unable to decode base64 data!'));
        }
        return $data;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorFile $file
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadAnyMissingChunk(
        PhabricatorUser $viewer,
        PhabricatorFile $file)
    {

        return $this->newChunkQuery($viewer, $file)
            ->withIsComplete(false)
            ->setLimit(1)
            ->execute();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorFile $file
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function newChunkQuery(
        PhabricatorUser $viewer,
        PhabricatorFile $file)
    {

        $engine = $file->instantiateStorageEngine();
        if (!$engine->isChunkEngine()) {
            throw new Exception(
                pht(
                    'File "%s" does not have chunks!',
                    $file->getPHID()));
        }

        return PhabricatorFileChunk::find()
            ->setViewer($viewer)
            ->withChunkHandles(array($file->getStorageHandle()));
    }


}
