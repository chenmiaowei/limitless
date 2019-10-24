<?php

namespace orangins\modules\metamta\engine;

use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Phobject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorMailEngineExtension
 * @package orangins\modules\metamta\engine
 * @author 陈妙威
 */
abstract class PhabricatorMailEngineExtension
    extends Phobject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $editor;

    /**
     * @return mixed
     * @throws \Exception
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
     * @param PhabricatorApplicationTransactionEditor $editor
     * @return $this
     * @author 陈妙威
     */
    final public function setEditor(
        PhabricatorApplicationTransactionEditor $editor)
    {
        $this->editor = $editor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function supportsObject($object);

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newMailStampTemplates($object);

    /**
     * @param $object
     * @param array $xactions
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newMailStamps($object, array $xactions);

    /**
     * @return PhabricatorMailEngineExtension[]
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final protected function getMailStamp($key)
    {
        return $this->getEditor()->getMailStamp($key);
    }

}
