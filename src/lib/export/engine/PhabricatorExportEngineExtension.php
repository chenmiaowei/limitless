<?php

namespace orangins\lib\export\engine;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorExportEngineExtension
 * @package orangins\lib\export\engine
 * @author 陈妙威
 */
abstract class PhabricatorExportEngineExtension extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('EXTENSIONKEY');
    }

    /**
     * @param $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer($viewer)
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
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject($object);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newExportFields();

    /**
     * @param array $objects
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newExportData(array $objects);

    /**
     * @return PhabricatorExportEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

}
