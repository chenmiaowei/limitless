<?php

namespace orangins\lib\export\format;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorExportFormat
 * @package orangins\lib\export\format
 * @author 陈妙威
 */
abstract class PhabricatorExportFormat
    extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var string
     */
    private $title;

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getExportFormatKey()
    {
        return $this->getPhobjectClassConstant('EXPORTKEY');
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    final public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    final public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExportFormatName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getMIMEContentType();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getFileExtension();

    /**
     * @param array $fields
     * @author 陈妙威
     */
    public function addHeaders(array $fields)
    {
        return;
    }

    /**
     * @param $object
     * @param array $fields
     * @param array $map
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addObject($object, array $fields, array $map);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newFileData();

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExportFormatEnabled()
    {
        return true;
    }

    /**
     * @return PhabricatorExportFormat[]
     * @author 陈妙威
     */
    final public static function getAllExportFormats()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getExportFormatKey')
            ->execute();
    }

}
