<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/8
 * Time: 4:32 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\db;

use Exception;

/**
 * Class Connection
 * @package orangins\lib\db
 * @author 陈妙威
 */
class Connection extends \yii\db\Connection
{
    /**
     * @var AphrontDatabaseTransactionState
     */
    private $transactionState;

    /**
     * @var array
     */
    private $locks = array();


    /**
     * @var array mapping between PDO driver names and [[Schema]] classes.
     * The keys of the array are PDO driver names while the values are either the corresponding
     * schema class names or configurations. Please refer to [[Yii::createObject()]] for
     * details on how to specify a configuration.
     *
     * This property is mainly used by [[getSchema()]] when fetching the database schema information.
     * You normally do not need to set this property unless you want to use your own
     * [[Schema]] class to support DBMS that is not supported by Yii.
     */
    public $schemaMap = [
        'pgsql' => 'yii\db\pgsql\Schema', // PostgreSQL
        'mysqli' => 'yii\db\mysql\Schema', // MySQL
        'mysql' => Schema::class, // MySQL
        'sqlite' => 'yii\db\sqlite\Schema', // sqlite 3
        'sqlite2' => 'yii\db\sqlite\Schema', // sqlite 2
        'sqlsrv' => 'yii\db\mssql\Schema', // newer MSSQL driver on MS Windows hosts
        'oci' => 'yii\db\oci\Schema', // Oracle driver
        'mssql' => 'yii\db\mssql\Schema', // older MSSQL driver on MS Windows hosts
        'dblib' => 'yii\db\mssql\Schema', // dblib drivers on GNU/Linux (and maybe other OSes) hosts
        'cubrid' => 'yii\db\cubrid\Schema', // CUBRID
    ];

    /* -(  Transaction Management  )--------------------------------------------- */


    /**
     * Begin a transaction, or set a savepoint if the connection is already
     * transactional.
     *
     * @return static
     * @task xaction
     * @throws \yii\db\Exception
     */
    public function openTransaction() {
        $state = $this->getTransactionState();
        $point = $state->getSavepointName();
        $depth = $state->getDepth();

        $new_transaction = ($depth == 0);
        if ($new_transaction) {
            $this->createCommand('START TRANSACTION')->execute();
        } else {
            $this->createCommand('SAVEPOINT '.$point)->execute();
        }

        $state->increaseDepth();

        return $this;
    }


    /**
     * Commit a transaction, or stage a savepoint for commit once the entire
     * transaction completes if inside a transaction stack.
     *
     * @return static
     * @task xaction
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function saveTransaction() {
        $state = $this->getTransactionState();
        $depth = $state->decreaseDepth();

        if ($depth == 0) {

            $this->createCommand('COMMIT')->execute();
        }

        return $this;
    }


    /**
     * Rollback a transaction, or unstage the last savepoint if inside a
     * transaction stack.
     *
     * @return static
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function killTransaction() {
        $state = $this->getTransactionState();
        $depth = $state->decreaseDepth();

        if ($depth == 0) {
            $this->createCommand('ROLLBACK')->execute();
        } else {
            $this->createCommand('ROLLBACK TO SAVEPOINT '.$state->getSavepointName())->execute();
        }

        return $this;
    }


    /**
     * Returns true if the connection is transactional.
     *
     * @return bool True if the connection is currently transactional.
     * @task xaction
     */
    public function isInsideTransaction() {
        $state = $this->getTransactionState();
        return ($state->getDepth() > 0);
    }


    /**
     * Get the current @{class:AphrontDatabaseTransactionState} object, or create
     * one if none exists.
     *
     * @return AphrontDatabaseTransactionState Current transaction state.
     * @task xaction
     */
    protected function getTransactionState() {
        if (!$this->transactionState) {
            $this->transactionState = new AphrontDatabaseTransactionState();
        }
        return $this->transactionState;
    }

    
    

    /**
     * @task xaction
     */
    public function beginReadLocking()
    {
        $this->getTransactionState()->beginReadLocking();
        return $this;
    }


    /**
     * @task xaction
     * @throws Exception
     */
    public function endReadLocking()
    {
        $this->getTransactionState()->endReadLocking();
        return $this;
    }


    /**
     * @task xaction
     */
    public function isReadLocking()
    {
        return $this->getTransactionState()->isReadLocking();
    }


    /**
     * @task xaction
     */
    public function beginWriteLocking()
    {
        $this->getTransactionState()->beginWriteLocking();
        return $this;
    }


    /**
     * @task xaction
     * @throws Exception
     */
    public function endWriteLocking()
    {
        $this->getTransactionState()->endWriteLocking();
        return $this;
    }


    /**
     * @task xaction
     */
    public function isWriteLocking()
    {
        return $this->getTransactionState()->isWriteLocking();
    }



    /* -(  Global Locks  )------------------------------------------------------- */


    /**
     * @param $lock
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function rememberLock($lock) {
        if (isset($this->locks[$lock])) {
            throw new Exception(
                \Yii::t("app",
                    'Trying to remember lock "{0}", but this lock has already been '.
                    'remembered.', [
                        $lock
                    ]));
        }

        $this->locks[$lock] = true;
        return $this;
    }


    /**
     * @param $lock
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function forgetLock($lock) {
        if (empty($this->locks[$lock])) {
            throw new Exception(
                \Yii::t("app",
                    'Trying to forget lock "{0}", but this connection does not remember '.
                    'that lock.', [
                        $lock
                    ]));
        }

        unset($this->locks[$lock]);
        return $this;
    }


    /**
     * @return $this
     * @author 陈妙威
     */
    public function forgetAllLocks() {
        $this->locks = array();
        return $this;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isHoldingAnyLock() {
        return (bool)$this->locks;
    }

}