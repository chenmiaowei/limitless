<?php

namespace orangins\lib\env;

use Exception;

/**
 * Configuration source which reads from a stack of other configuration
 * sources.
 *
 * This source is writable if any source in the stack is writable. Writes happen
 * to the first writable source only.
 */
final class PhabricatorConfigStackSource
    extends PhabricatorConfigSource
{

    /**
     * @var PhabricatorConfigSource[]
     */
    private $stack = array();

    /**
     * @param PhabricatorConfigSource $source
     * @return $this
     */
    public function pushSource(PhabricatorConfigSource $source)
    {
        array_unshift($this->stack, $source);
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function popSource()
    {
        if (empty($this->stack)) {
            throw new Exception(\Yii::t('app', 'Popping an empty {0}!', [
                __CLASS__
            ]));
        }
        return array_shift($this->stack);
    }

    /**
     * @return PhabricatorConfigSource[]
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * @param array $keys
     * @return array|mixed
     */
    public function getKeys(array $keys)
    {
        $result = array();
        foreach ($this->stack as $source) {
            $result = $result + $source->getKeys($keys);
        }
        return $result;
    }

    /**
     * @return array|mixed
     */
    public function getAllKeys()
    {
        $result = array();
        foreach ($this->stack as $source) {
            $result = $result + $source->getAllKeys();
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function canWrite()
    {
        foreach ($this->stack as $source) {
            if ($source->canWrite()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $keys
     */
    public function setKeys(array $keys)
    {
        foreach ($this->stack as $source) {
            if ($source->canWrite()) {
                $source->setKeys($keys);
                return;
            }
        }

        // We can't write; this will throw an appropriate exception.
        parent::setKeys($keys);
    }

    /**
     * @param array $keys
     */
    public function deleteKeys(array $keys)
    {
        foreach ($this->stack as $source) {
            if ($source->canWrite()) {
                $source->deleteKeys($keys);
                return;
            }
        }

        // We can't write; this will throw an appropriate exception.
        parent::deleteKeys($keys);
    }

}
