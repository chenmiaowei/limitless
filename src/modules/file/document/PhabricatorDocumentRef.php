<?php

namespace orangins\modules\file\document;

use orangins\lib\OranginsObject;
use orangins\modules\file\models\PhabricatorFile;
use PhutilMethodNotImplementedException;
use Exception;

/**
 * Class PhabricatorDocumentRef
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorDocumentRef
    extends OranginsObject
{

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $mimeType;
    /**
     * @var PhabricatorFile
     */
    private $file;
    /**
     * @var int
     */
    private $byteLength;
    /**
     * @var
     */
    private $snippet;
    /**
     * @var array
     */
    private $symbolMetadata = array();
    /**
     * @var
     */
    private $blameURI;
    /**
     * @var array
     */
    private $coverage = array();

    /**
     * @param PhabricatorFile $file
     * @return $this
     * @author 陈妙威
     */
    public function setFile(PhabricatorFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $mime_type
     * @return $this
     * @author 陈妙威
     */
    public function setMimeType($mime_type)
    {
        $this->mimeType = $mime_type;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getMimeType()
    {
        if ($this->mimeType !== null) {
            return $this->mimeType;
        }

        if ($this->file) {
            return $this->file->getMimeType();
        }

        return null;
    }

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
     * @return null
     * @author 陈妙威
     */
    public function getName()
    {
        if ($this->name !== null) {
            return $this->name;
        }

        if ($this->file) {
            return $this->file->getName();
        }

        return null;
    }

    /**
     * @param $length
     * @return $this
     * @author 陈妙威
     */
    public function setByteLength($length)
    {
        $this->byteLength = $length;
        return $this;
    }

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getByteLength()
    {
        if ($this->byteLength !== null) {
            return $this->byteLength;
        }

        if ($this->file) {
            return (int)$this->file->getByteSize();
        }

        return null;
    }

    /**
     * @param null $begin
     * @param null $end
     * @return string
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function loadData($begin = null, $end = null)
    {
        if ($this->file) {
            $iterator = $this->file->getFileDataIterator($begin, $end);

            $result = '';
            foreach ($iterator as $chunk) {
                $result .= $chunk;
            }
            return $result;
        }

        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param array $candidate_types
     * @return bool
     * @author 陈妙威
     */
    public function hasAnyMimeType(array $candidate_types)
    {
        $mime_full = $this->getMimeType();
        $mime_parts = explode(';', $mime_full);

        $mime_type = head($mime_parts);
        $mime_type = $this->normalizeMimeType($mime_type);

        foreach ($candidate_types as $candidate_type) {
            if ($this->normalizeMimeType($candidate_type) === $mime_type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $mime_type
     * @return string
     * @author 陈妙威
     */
    private function normalizeMimeType($mime_type)
    {
        $mime_type = trim($mime_type);
        $mime_type = phutil_utf8_strtolower($mime_type);
        return $mime_type;
    }

    /**
     * @return bool
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function isProbablyText()
    {
        $snippet = $this->getSnippet();
        return (strpos($snippet, "\0") === false);
    }

    /**
     * @return bool
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function isProbablyJSON()
    {
        if (!$this->isProbablyText()) {
            return false;
        }

        $snippet = $this->getSnippet();

        // If the file is longer than the snippet, we don't detect the content
        // as JSON. We could use some kind of heuristic here if we wanted, but
        // see PHI749 for a false positive.
        if (strlen($snippet) < $this->getByteLength()) {
            return false;
        }

        // If the snippet is the whole file, just check if the snippet is valid
        // JSON. Note that `phutil_json_decode()` only accepts arrays and objects
        // as JSON, so this won't misfire on files with content like "3".
        try {
            phutil_json_decode($snippet);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return string
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getSnippet()
    {
        if ($this->snippet === null) {
            $this->snippet = $this->loadData(null, (1024 * 1024 * 1));
        }

        return $this->snippet;
    }

    /**
     * @param array $metadata
     * @return $this
     * @author 陈妙威
     */
    public function setSymbolMetadata(array $metadata)
    {
        $this->symbolMetadata = $metadata;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getSymbolMetadata()
    {
        return $this->symbolMetadata;
    }

    /**
     * @param $blame_uri
     * @return $this
     * @author 陈妙威
     */
    public function setBlameURI($blame_uri)
    {
        $this->blameURI = $blame_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBlameURI()
    {
        return $this->blameURI;
    }

    /**
     * @param $coverage
     * @return $this
     * @author 陈妙威
     */
    public function addCoverage($coverage)
    {
        $this->coverage[] = array(
            'data' => $coverage,
        );
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCoverage()
    {
        return $this->coverage;
    }

}
