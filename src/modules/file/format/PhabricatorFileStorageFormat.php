<?php

namespace orangins\modules\file\format;

use orangins\lib\OranginsObject;
use PhutilMethodNotImplementedException;
use PhutilInvalidStateException;
use PhutilClassMapQuery;
use orangins\modules\file\models\PhabricatorFile;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileStorageFormat
 * @package orangins\modules\file\format
 * @author 陈妙威
 */
abstract class PhabricatorFileStorageFormat extends OranginsObject
{

    /**
     * @var
     */
    private $file;

    /**
     * @param PhabricatorFile $file
     * @return $this
     * @author 陈妙威
     */
    final public function setFile(PhabricatorFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function getFile()
    {
        if (!$this->file) {
            throw new PhutilInvalidStateException('setFile');
        }
        return $this->file;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getStorageFormatName();

    /**
     * @param $raw_iterator
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newReadIterator($raw_iterator);

    /**
     * @param $raw_iterator
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newWriteIterator($raw_iterator);

    /**
     * @return null
     * @author 陈妙威
     */
    public function newFormatIntegrityHash()
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function newStorageProperties()
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canGenerateNewKeyMaterial()
    {
        return false;
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function generateNewKeyMaterial()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canCycleMasterKey()
    {
        return false;
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function cycleStorageProperties()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $key_name
     * @throws Exception
     * @author 陈妙威
     */
    public function selectMasterKey($key_name)
    {
        throw new Exception(
            \Yii::t("app",
                'This storage format ("%s") does not support key selection.',
                $this->getStorageFormatName()));
    }

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getStorageFormatKey()
    {
        return $this->getPhobjectClassConstant('FORMATKEY');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllFormats()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorFileStorageFormat::class)
            ->setUniqueMethod('getStorageFormatKey')
            ->execute();
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final public static function getFormat($key)
    {
        $formats = self::getAllFormats();
        return ArrayHelper::getValue($formats, $key);
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public static function requireFormat($key)
    {
        $format = self::getFormat($key);

        if (!$format) {
            throw new Exception(
                \Yii::t("app",
                    'No file storage format with key "%s" exists.',
                    $key));
        }

        return $format;
    }

}
