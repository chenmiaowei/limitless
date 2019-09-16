<?php

namespace orangins\modules\system\engine;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorDestructionEngineExtension
 * @package orangins\modules\system\engine
 * @author 陈妙威
 */
abstract class PhabricatorDestructionEngineExtension extends OranginsObject
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
     * @param PhabricatorDestructionEngine $engine
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canDestroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {
        return true;
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object);

    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
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
