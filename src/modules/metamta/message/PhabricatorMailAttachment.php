<?php

namespace orangins\modules\metamta\message;

use ArrayIterator;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\uploadsource\PhabricatorIteratorFileUploadSource;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;
use Phobject;

/**
 * Class PhabricatorMailAttachment
 * @package orangins\modules\metamta\message
 * @author 陈妙威
 */
final class PhabricatorMailAttachment extends Phobject
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
     * @var
     */
    private $file;
    /**
     * @var
     */
    private $filePHID;

    /**
     * PhabricatorMailAttachment constructor.
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
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \orangins\modules\file\uploadsource\PhabricatorFileUploadSourceByteLimitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
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
     * @return PhabricatorMailAttachment
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public static function newFromDictionary(array $dict)
    {
        $file = null;

        $file_phid = idx($dict, 'filePHID');
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
            idx($dict, 'data'),
            idx($dict, 'filename'),
            idx($dict, 'mimetype'));

        if ($file) {
            $attachment->file = $file;
        }

        return $attachment;
    }

}
