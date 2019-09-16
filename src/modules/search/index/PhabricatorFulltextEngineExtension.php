<?php

namespace orangins\modules\search\index;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorFulltextEngineExtension
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
abstract class PhabricatorFulltextEngineExtension extends OranginsObject
{

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
     * @return PhabricatorUser
     * @author 陈妙威
     */
    final protected function getViewer()
    {
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionName();

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldEnrichFulltextObject($object)
    {
        return false;
    }

    /**
     * @param $object
     * @param PhabricatorSearchAbstractDocument $document
     * @author 陈妙威
     */
    public function enrichFulltextObject(
        $object,
        PhabricatorSearchAbstractDocument $document)
    {
        return;
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldIndexFulltextObject($object)
    {
        return false;
    }

    /**
     * @param $object
     * @param PhabricatorSearchAbstractDocument $document
     * @author 陈妙威
     */
    public function indexFulltextObject(
        $object,
        PhabricatorSearchAbstractDocument $document)
    {
        return;
    }

    /**
     * @return PhabricatorFulltextEngineExtension[]
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
