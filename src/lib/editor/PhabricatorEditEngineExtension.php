<?php

namespace orangins\lib\editor;

use orangins\lib\OranginsObject;
use orangins\lib\db\ActiveRecord;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\widgets\ActiveField;

/**
 * 字段编辑插件
 * Class PhabricatorEditEngineExtension
 * @package orangins\lib\editor
 * @author 陈妙威
 */
abstract class PhabricatorEditEngineExtension extends OranginsObject
{
    /**
     * 用户
     * @var PhabricatorUser
     */
    private $viewer;

    /**
     * 获取插件的主键
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
     * 获取插件的优先级（用于排序）
     * @return int
     * @author 陈妙威
     */
    public function getExtensionPriority()
    {
        return 1000;
    }

    /**
     * 插件是否可用
     * @return mixed
     * @author 陈妙威
     */
    abstract public function isExtensionEnabled();

    /**
     * 插件的名称
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionName();

    /**
     * 当前数据对象是否支持当前字段编辑插件
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object);

    /**
     * 渲染字段编辑插件
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return PhabricatorEditField[]
     * @author 陈妙威
     */
    abstract public function buildCustomEditFields(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object);

    /**
     * @param PhabricatorEditEngine $engine
     * @return array
     * @author 陈妙威
     */
    public function newBulkEditGroups(PhabricatorEditEngine $engine)
    {
        return array();
    }

    /**
     * 获取所有的字段编辑插件扩展
     * @return static[]
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorEditEngineExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->setSortMethod('getExtensionPriority')
            ->execute();
    }

    /**
     * 获取所有的可用的字段编辑插件扩展
     * @return static[]
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
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
