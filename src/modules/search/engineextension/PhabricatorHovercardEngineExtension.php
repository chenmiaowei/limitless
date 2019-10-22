<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\lib\view\phui\PHUIHovercardView;
use ReflectionException;

/**
 * Class PhabricatorHovercardEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorHovercardEngineExtension extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;

    /**
     * @return string
     * @throws ReflectionException
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
    abstract public function canRenderObjectHovercard($object);

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 5000;
    }

    /**
     * @param array $objects
     * @return null
     * @author 陈妙威
     */
    public function willRenderHovercards(array $objects)
    {
        return null;
    }

    /**
     * @param PHUIHovercardView $hovercard
     * @param PhabricatorObjectHandle $handle
     * @param $object
     * @param $data
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderHovercard(
        PHUIHovercardView $hovercard,
        PhabricatorObjectHandle $handle,
        $object,
        $data);

    /**
     * @return PhabricatorHovercardEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorHovercardEngineExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->setSortMethod('getExtensionOrder')
            ->execute();
    }

    /**
     * @return PhabricatorHovercardEngineExtension[]
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
