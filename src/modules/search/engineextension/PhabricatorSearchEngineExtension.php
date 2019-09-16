<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;

/**
 * Class PhabricatorSearchEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorSearchEngineExtension extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $searchEngine;

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
     * @return static
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
     * @param PhabricatorApplicationSearchEngine $engine
     * @return $this
     * @author 陈妙威
     */
    final public function setSearchEngine(
        PhabricatorApplicationSearchEngine $engine)
    {
        $this->searchEngine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getSearchEngine()
    {
        return $this->searchEngine;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function isExtensionEnabled();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionName();

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject($object);

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 7000;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchFields($object)
    {
        return array();
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getSearchAttachments($object)
    {
        return array();
    }

    /**
     * @param $object
     * @param $query
     * @param PhabricatorSavedQuery $saved
     * @param array $map
     * @author 陈妙威
     */
    public function applyConstraintsToQuery(
        $object,
        $query,
        PhabricatorSavedQuery $saved,
        array $map)
    {
        return;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function getFieldSpecificationsForConduit($object)
    {
        return array();
    }

    /**
     * @param array $objects
     * @return null
     * @author 陈妙威
     */
    public function loadExtensionConduitData(array $objects)
    {
        return null;
    }

    /**
     * @param $object
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($object, $data)
    {
        return array();
    }

    /**
     * @return PhabricatorSearchEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorSearchEngineExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->setSortMethod('getExtensionOrder')
            ->execute();
    }

    /**
     * @return PhabricatorSearchEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllEnabledExtensions()
    {
        $extensions = self::getAllExtensions();
        foreach ($extensions as $key => $extension) {
            if (!$extension->isExtensionEnabled()) {
                unset($extensions[$key]);
            }
        }

        return $extensions;
    }

}
