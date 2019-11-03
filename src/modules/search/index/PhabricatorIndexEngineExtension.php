<?php

namespace orangins\modules\search\index;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use ReflectionException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorIndexEngineExtension
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
abstract class PhabricatorIndexEngineExtension extends OranginsObject
{

    /**
     * @var
     */
    private $parameters;
    /**
     * @var
     */
    private $forceFullReindex;

    /**
     * @param array $parameters
     * @return $this
     * @author 陈妙威
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    public function getParameter($key, $default = null)
    {
        return ArrayHelper::getValue($this->parameters, $key, $default);
    }

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
     * @return mixed
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
     * @return mixed
     * @author 陈妙威
     */
    abstract public function shouldIndexObject($object);

    /**
     * @param PhabricatorIndexEngine $engine
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function indexObject(
        PhabricatorIndexEngine $engine,
        $object);

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    public function getIndexVersion($object)
    {
        return null;
    }

    /**
     * @return PhabricatorIndexEngineExtension[]
     * @throws PhutilInvalidStateException
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
     * @return object
     * @author 陈妙威
     */
    final public function shouldForceFullReindex()
    {
        return $this->getParameter('force');
    }

}
