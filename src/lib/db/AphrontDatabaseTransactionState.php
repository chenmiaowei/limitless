<?php

namespace orangins\lib\db;

use orangins\lib\OranginsObject;
use Exception;

/**
 * Represents current transaction state of a connection.
 */
final class AphrontDatabaseTransactionState extends OranginsObject
{

    /**
     * @var int
     */
    private $depth = 0;
    /**
     * @var int
     */
    private $readLockLevel = 0;
    /**
     * @var int
     */
    private $writeLockLevel = 0;

    /**
     * @return int
     * @author 陈妙威
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function increaseDepth()
    {
        return ++$this->depth;
    }

    /**
     * @return int
     * @throws Exception
     * @author 陈妙威
     */
    public function decreaseDepth()
    {
        if ($this->depth == 0) {
            throw new Exception(
                \Yii::t("app",
                    'Too many calls to {0} or {1}!', [
                        'saveTransaction()',
                        'killTransaction()'
                    ]));
        }

        return --$this->depth;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSavepointName()
    {
        return 'Aphront_Savepoint_' . $this->depth;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function beginReadLocking()
    {
        $this->readLockLevel++;
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function endReadLocking()
    {
        if ($this->readLockLevel == 0) {
            throw new Exception(
                \Yii::t("app",
                    'Too many calls to {0}!',
                     [
                         __FUNCTION__ . '()'
                     ]));
        }
        $this->readLockLevel--;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isReadLocking()
    {
        return ($this->readLockLevel > 0);
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function beginWriteLocking()
    {
        $this->writeLockLevel++;
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function endWriteLocking()
    {
        if ($this->writeLockLevel == 0) {
            throw new Exception(
                \Yii::t("app",
                    'Too many calls to {0}!', [
                        __FUNCTION__ . '()'
                    ]));
        }
        $this->writeLockLevel--;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isWriteLocking()
    {
        return ($this->writeLockLevel > 0);
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        if ($this->depth) {
            throw new Exception(
                \Yii::t("app",
                    'Process exited with an open transaction! The transaction ' .
                    'will be implicitly rolled back. Calls to {0} must always be ' .
                    'paired with a call to {1} or {2}.', [
                        'openTransaction()',
                        'saveTransaction()',
                        'killTransaction()'
                    ]));
        }
        if ($this->readLockLevel) {
            throw new Exception(
                \Yii::t("app",
                    'Process exited with an open read lock! Call to {0} ' .
                    'must always be paired with a call to {1}.',[
                        'beginReadLocking()',
                        'endReadLocking()'
                    ]));
        }
        if ($this->writeLockLevel) {
            throw new Exception(
                \Yii::t("app",
                    'Process exited with an open write lock! Call to {0} ' .
                    'must always be paired with a call to {1}.',[
                        'beginWriteLocking()',
                        'endWriteLocking()'
                    ]));
        }
    }
}
