<?php

namespace orangins\modules\metamta\models;

use ArrayIterator;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMetaMTAAttachment
 * @package orangins\modules\metamta\models
 * @author 陈妙威
 */
final class PhabricatorMetaMTAAttachment extends OranginsObject
{

    /**
     * @var
     */
    private $data;
    /**
     * @var
     */
    private $filename;
    /**
     * @var
     */
    private $mimetype;
    /**
     * @var PhabricatorFile
     */
    private $file;
    /**
     * @var
     */
    private $filePHID;

    /**
     * PhabricatorMetaMTAAttachment constructor.
     * @param $data
     * @param $filename
     * @param $mimetype
     */
    public function __construct($data, $filename, $mimetype)
    {
        $this->setData($data);
        $this->setFilename($filename);
        $this->setMimeType($mimetype);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return $this
     * @author 陈妙威
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param $filename
     * @return $this
     * @author 陈妙威
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * @param $mimetype
     * @return $this
     * @author 陈妙威
     */
    public function setMimeType($mimetype)
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        if (!$this->file) {
            $iterator = new ArrayIterator(array($this->getData()));

            $source = (new PhabricatorIteratorFileUploadSource())
                ->setName($this->getFilename())
                ->setViewPolicy(PhabricatorPolicies::POLICY_NOONE)
                ->setMIMEType($this->getMimeType())
                ->setIterator($iterator);

            $this->file = $source->uploadFile();
        }

        return array(
            'filename' => $this->getFilename(),
            'mimetype' => $this->getMimeType(),
            'filePHID' => $this->file->getPHID(),
        );
    }

    /**
     * @param array $dict
     * @return PhabricatorMetaMTAAttachment
     * @author 陈妙威
     */
    public static function newFromDictionary(array $dict)
    {
        $file = null;

        $file_phid = ArrayHelper::getValue($dict, 'filePHID');
        if ($file_phid) {
            $file = PhabricatorFile::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs(array($file_phid))
                ->executeOne();
            if ($file) {
                $dict['data'] = $file->loadFileData();
            }
        }

        $attachment = new self(
            ArrayHelper::getValue($dict, 'data'),
            ArrayHelper::getValue($dict, 'filename'),
            ArrayHelper::getValue($dict, 'mimetype'));

        if ($file) {
            $attachment->file = $file;
        }

        return $attachment;
    }

}
