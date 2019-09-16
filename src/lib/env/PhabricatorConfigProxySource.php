<?php
namespace orangins\lib\env;

use Exception;

/**
 * Configuration source which proxies some other configuration source.
 */
abstract class PhabricatorConfigProxySource
  extends PhabricatorConfigSource {

    /**
     * @var PhabricatorConfigDictionarySource
     */
    private $source;

    /**
     * @return PhabricatorConfigDictionarySource
     * @throws Exception
     */
    final protected function getSource() {
    if (!$this->source) {
      throw new Exception(\Yii::t('app', 'No configuration source set!'));
    }
    return $this->source;
  }

    /**
     * @param PhabricatorConfigSource $source
     * @return $this
     */
    final protected function setSource(PhabricatorConfigSource $source) {
    $this->source = $source;
    return $this;
  }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAllKeys() {
    return $this->getSource()->getAllKeys();
  }

    /**
     * @param array $keys
     * @return mixed
     * @throws Exception
     */
    public function getKeys(array $keys) {
    return $this->getSource()->getKeys($keys);
  }

    /**
     * @return bool|mixed
     * @throws Exception
     */
    public function canWrite() {
    return $this->getSource()->canWrite();
  }

    /**
     * @param array $keys
     * @return $this|void
     * @throws Exception
     */
    public function setKeys(array $keys) {
    $this->getSource()->setKeys($keys);
    return $this;
  }

    /**
     * @param array $keys
     * @return $this|void
     * @throws Exception
     */
    public function deleteKeys(array $keys) {
    $this->getSource()->deleteKeys($keys);
    return $this;
  }

    /**
     * @param $name
     * @return $this|PhabricatorConfigSource
     * @throws Exception
     */
    public function setName($name) {
    $this->getSource()->setName($name);
    return $this;
  }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getName() {
    return $this->getSource()->getName();
  }

}
