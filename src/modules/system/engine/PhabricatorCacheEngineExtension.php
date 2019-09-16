<?php

namespace orangins\modules\system\engine;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorCacheEngineExtension
 * @package orangins\modules\system\engine
 * @author 陈妙威
 */
abstract class PhabricatorCacheEngineExtension extends OranginsObject
{

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('EXTENSIONKEY');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionName();

    /**
     * @param PhabricatorCacheEngine $engine
     * @param array $objects
     * @return array
     * @author 陈妙威
     */
    public function discoverLinkedObjects(
        PhabricatorCacheEngine $engine,
        array $objects)
    {
        return array();
    }

    /**
     * @param PhabricatorCacheEngine $engine
     * @param array $objects
     * @return null
     * @author 陈妙威
     */
    public function deleteCaches(
        PhabricatorCacheEngine $engine,
        array $objects)
    {
        return null;
    }

    /**
     * @return static[]
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorCacheEngineExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

    /**
     * @param array $objects
     * @param $class_name
     * @return array
     * @author 陈妙威
     */
    final public function selectObjects(array $objects, $class_name)
    {
        $results = array();

        foreach ($objects as $phid => $object) {
            if ($object instanceof $class_name) {
                $results[$phid] = $object;
            }
        }

        return $results;
    }

}
