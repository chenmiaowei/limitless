<?php

namespace orangins\lib\env;

use orangins\lib\OranginsObject;
use Exception;

/**
 * Class OranginsConfigSource
 * @package orangins\lib\env
 */
abstract class PhabricatorConfigSource extends OranginsObject
{

    /**
     * @var
     */
    private $name;

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $keys
     * @return mixed
     */
    abstract public function getKeys(array $keys);

    /**
     * @return mixed
     */
    abstract public function getAllKeys();

    /**
     * @return bool
     */
    public function canWrite()
    {
        return false;
    }

    /**
     * @param array $keys
     * @throws Exception
     */
    public function setKeys(array $keys)
    {
        throw new Exception(
            \Yii::t('app', 'This configuration source does not support writes.'));
    }

    /**
     * @param array $keys
     * @throws Exception
     */
    public function deleteKeys(array $keys)
    {
        throw new Exception(
            \Yii::t('app', 'This configuration source does not support writes.'));
    }

}
