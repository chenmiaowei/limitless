<?php

namespace orangins\modules\file\uploadsource;

use Exception;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\OranginsObject;
use orangins\modules\file\engine\PhabricatorChunkedFileStorageEngine;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileChunk;
use orangins\modules\policy\constants\PhabricatorPolicies;
use PhutilRope;

/**
 * Class PhabricatorFileUploadSource
 * @package orangins\modules\file\uploadsource
 * @author 陈妙威
 */
abstract class PhabricatorFileUploadSource
    extends OranginsObject
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $relativeTTL;
    /**
     * @var
     */
    private $viewPolicy;
    /**
     * @var
     */
    private $mimeType;
    /**
     * @var
     */
    private $authorPHID;

    /**
     * @var
     */
    private $rope;
    /**
     * @var
     */
    private $data;
    /**
     * @var
     */
    private $shouldChunk;
    /**
     * @var
     */
    private $didRewind;
    /**
     * @var int
     */
    private $totalBytesWritten = 0;
    /**
     * @var int
     */
    private $totalBytesRead = 0;
    /**
     * @var int
     */
    private $byteLimit = 0;

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $relative_ttl
     * @return $this
     * @author 陈妙威
     */
    public function setRelativeTTL($relative_ttl)
    {
        $this->relativeTTL = $relative_ttl;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRelativeTTL()
    {
        return $this->relativeTTL;
    }

    /**
     * @param $view_policy
     * @return $this
     * @author 陈妙威
     */
    public function setViewPolicy($view_policy)
    {
        $this->viewPolicy = $view_policy;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewPolicy()
    {
        return $this->viewPolicy;
    }

    /**
     * @param $byte_limit
     * @return $this
     * @author 陈妙威
     */
    public function setByteLimit($byte_limit)
    {
        $this->byteLimit = $byte_limit;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getByteLimit()
    {
        return $this->byteLimit;
    }

    /**
     * @param $mime_type
     * @return $this
     * @author 陈妙威
     */
    public function setMIMEType($mime_type)
    {
        $this->mimeType = $mime_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMIMEType()
    {
        return $this->mimeType;
    }

    /**
     * @param $author_phid
     * @return $this
     * @author 陈妙威
     */
    public function setAuthorPHID($author_phid)
    {
        $this->authorPHID = $author_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAuthorPHID()
    {
        return $this->authorPHID;
    }

    /**
     * @return mixed
     * @throws PhabricatorFileUploadSourceByteLimitException
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function uploadFile()
    {
        if (!$this->shouldChunkFile()) {
            return $this->writeSingleFile();
        } else {
            return $this->writeChunkedFile();
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getDataIterator()
    {
        if (!$this->data) {
            $this->data = $this->newDataIterator();
        }
        return $this->data;
    }

    /**
     * @return PhutilRope
     * @author 陈妙威
     */
    private function getRope()
    {
        if (!$this->rope) {
            $this->rope = new PhutilRope();
        }
        return $this->rope;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newDataIterator();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getDataLength();

    /**
     * @return bool
     * @throws PhabricatorFileUploadSourceByteLimitException
     * @author 陈妙威
     */
    private function readFileData()
    {
        $data = $this->getDataIterator();

        if (!$this->didRewind) {
            $data->rewind();
            $this->didRewind = true;
        } else {
            if ($data->valid()) {
                $data->next();
            }
        }

        if (!$data->valid()) {
            return false;
        }

        $read_bytes = $data->current();
        $this->totalBytesRead += strlen($read_bytes);

        if ($this->byteLimit && ($this->totalBytesRead > $this->byteLimit)) {
            throw new PhabricatorFileUploadSourceByteLimitException();
        }

        $rope = $this->getRope();
        $rope->append($read_bytes);

        return true;
    }

    /**
     * @return bool
     * @throws PhabricatorFileUploadSourceByteLimitException
     * @author 陈妙威
     */
    private function shouldChunkFile()
    {
        if ($this->shouldChunk !== null) {
            return $this->shouldChunk;
        }

        $threshold = PhabricatorFileStorageEngine::getChunkThreshold();

        if ($threshold === null) {
            // If there are no chunk engines available, we clearly can't chunk the
            // file.
            $this->shouldChunk = false;
        } else {
            // If we don't know how large the file is, we're going to read some data
            // from it until we know whether it's a small file or not. This will give
            // us enough information to make a decision about chunking.
            $length = $this->getDataLength();
            if ($length === null) {
                $rope = $this->getRope();
                while ($this->readFileData()) {
                    $length = $rope->getByteLength();
                    if ($length > $threshold) {
                        break;
                    }
                }
            }

            $this->shouldChunk = ($length > $threshold);
        }

        return $this->shouldChunk;
    }

    /**
     * @return mixed
     * @throws PhabricatorFileUploadSourceByteLimitException
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    private function writeSingleFile()
    {
        while ($this->readFileData()) {
            // Read the entire file.
        }

        $rope = $this->getRope();
        $data = $rope->getAsString();

        $parameters = $this->getNewFileParameters();

        return PhabricatorFile::newFromFileData($data, $parameters);
    }

    /**
     * @return mixed
     * @throws PhabricatorFileUploadSourceByteLimitException
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException*@throws Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function writeChunkedFile()
    {
        $engine = $this->getChunkEngine();

        $parameters = $this->getNewFileParameters();

        $data_length = $this->getDataLength();
        if ($data_length !== null) {
            $length = $data_length;
        } else {
            $length = 0;
        }

        $file = PhabricatorFile::newChunkedFile($engine, $length, $parameters);
        $file->saveAndIndex();

        $rope = $this->getRope();

        // Read the source, writing chunks as we get enough data.
        while ($this->readFileData()) {
            while (true) {
                $rope_length = $rope->getByteLength();
                if ($rope_length < $engine->getChunkSize()) {
                    break;
                }
                $this->writeChunk($file, $engine);
            }
        }

        // If we have extra bytes at the end, write them. Note that it's possible
        // that we have more than one chunk of bytes left if the read was very
        // fast.
        while ($rope->getByteLength()) {
            $this->writeChunk($file, $engine);
        }

        $file->setIsPartial(0);
        if ($data_length === null) {
            $file->setByteSize($this->getTotalBytesWritten());
        }
        $file->save();

        return $file;
    }

    /**
     * @param PhabricatorFile $file
     * @param PhabricatorFileStorageEngine|PhabricatorChunkedFileStorageEngine $engine
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    private function writeChunk(
        PhabricatorFile $file,
        PhabricatorFileStorageEngine $engine)
    {

        $offset = $this->getTotalBytesWritten();
        $max_length = $engine->getChunkSize();
        $rope = $this->getRope();

        $data = $rope->getPrefixBytes($max_length);
        $actual_length = strlen($data);
        $rope->removeBytesFromHead($actual_length);

        $params = array(
            'name' => $file->getMonogram() . '.chunk-' . $offset,
            'viewPolicy' => PhabricatorPolicies::POLICY_NOONE,
            'chunk' => true,
        );

        // If this isn't the initial chunk, provide a dummy MIME type so we do not
        // try to detect it. See T12857.
        if ($offset > 0) {
            $params['mime-type'] = 'application/octet-stream';
        }

        $chunk_data = PhabricatorFile::newFromFileData($data, $params);

        $chunk = PhabricatorFileChunk::initializeNewChunk(
            $file->getStorageHandle(),
            $offset,
            $offset + $actual_length);

        $chunk->setDataFilePHID($chunk_data->getPHID());
        if (!$chunk->save()) {
            throw new ActiveRecordException("File chunk save error. ", $chunk->getErrorSummary(true));
        }

        $this->setTotalBytesWritten($offset + $actual_length);

        return $chunk;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function getNewFileParameters()
    {
        $parameters = array(
            'name' => $this->getName(),
            'viewPolicy' => $this->getViewPolicy(),
        );

        $ttl = $this->getRelativeTTL();
        if ($ttl !== null) {
            $parameters['ttl.relative'] = $ttl;
        }

        $mime_type = $this->getMimeType();
        if ($mime_type !== null) {
            $parameters['mime-type'] = $mime_type;
        }

        $author_phid = $this->getAuthorPHID();
        if ($author_phid !== null) {
            $parameters['authorPHID'] = $author_phid;
        }

        return $parameters;
    }

    /**
     * @author 陈妙威
     * @return PhabricatorChunkedFileStorageEngine
     * @throws Exception
     */
    private function getChunkEngine()
    {
        $chunk_engines = PhabricatorFileStorageEngine::loadWritableChunkEngines();
        if (!$chunk_engines) {
            throw new Exception(
                \Yii::t("app",
                    'Unable to upload file: this server is not configured with any ' .
                    'storage engine which can store large files.'));
        }

        return head($chunk_engines);
    }

    /**
     * @param $total_bytes_written
     * @return $this
     * @author 陈妙威
     */
    private function setTotalBytesWritten($total_bytes_written)
    {
        $this->totalBytesWritten = $total_bytes_written;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    private function getTotalBytesWritten()
    {
        return $this->totalBytesWritten;
    }

}
