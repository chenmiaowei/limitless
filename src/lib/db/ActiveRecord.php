<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 3:19 PM
 */

namespace orangins\lib\db;

use AphrontObjectMissingQueryException;
use AphrontQueryException;
use orangins\lib\behaviors\TimestampBehavior;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\LiskEphemeralObjectException;
use AphrontAccessDeniedQueryException;
use AphrontConnectionLostQueryException;
use AphrontDeadlockQueryException;
use AphrontDuplicateKeyQueryException;
use AphrontInvalidCredentialsQueryException;
use AphrontLockTimeoutQueryException;
use AphrontSchemaQueryException;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use Yii;
use Exception;
use yii\db\ActiveQuery;
use yii\db\Command;
use yii\db\IntegrityException;
use yii\db\Schema;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveRecord
 * @package orangins\lib\models
 * @method Connection getDb()
 * @author 陈妙威
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     *
     */
    const COUNTER_TABLE_NAME = 'worker_lisk_counter';
    /**
     * @var
     */
    public $_hashKey;
    /**
     *
     */
    const ATTACHABLE = '<attachable>';


    /**
     * @var bool
     */
    private $ephemeral = false;


    /**
     * @var array
     */
    private static $namespaceStack = array();


    /* -(  Configuring Storage  )------------------------------------------------ */

    /**
     * @task config
     */
    public static function pushStorageNamespace($namespace)
    {
        self::$namespaceStack[] = $namespace;
    }

    /**
     * @task config
     */
    public static function popStorageNamespace()
    {
        array_pop(self::$namespaceStack);
    }

    /**
     * @task config
     * @throws Exception
     */
    public static function getDefaultStorageNamespace()
    {
        return PhabricatorEnv::getEnvConfig('storage.default-namespace');
    }

    /**
     * @task config
     * @throws Exception
     */
    public static function getStorageNamespace()
    {
        $namespace = end(self::$namespaceStack);
        if (!strlen($namespace)) {
            $namespace = self::getDefaultStorageNamespace();
        }
        if (!strlen($namespace)) {
            throw new Exception(\Yii::t("app", 'No storage namespace configured!'));
        }
        return $namespace;
    }

    /**
     * Increments a named counter and returns the next value.
     *
     * @param  Connection   Database where the counter resides.
     * @param   string                      Counter name to create or increment.
     * @return  int                         Next counter value.
     *
     * @task util
     * @throws \yii\db\Exception
     */
    public static function loadNextCounterValue(
        Connection $conn_w,
        $counter_name)
    {
        // NOTE: If an insert does not touch an autoincrement row or call
        // LAST_INSERT_ID(), MySQL normally does not change the value of
        // LAST_INSERT_ID(). This can cause a counter's value to leak to a
        // new counter if the second counter is created after the first one is
        // updated. To avoid this, we insert LAST_INSERT_ID(1), to ensure the
        // LAST_INSERT_ID() is always updated and always set correctly after the
        // query completes.
        $lastModelId = Yii::$app->db->createCommand("
        SELECT `AUTO_INCREMENT`
    FROM  INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND   TABLE_NAME   = :TableName", [
            ':TableName' => self::COUNTER_TABLE_NAME,
        ])->queryScalar();
        $lastModelId = intval($lastModelId);
        $conn_w->createCommand("INSERT INTO " . self::COUNTER_TABLE_NAME . " (counter_name, counter_value) VALUES
          (:counter_name, :counter_value)
        ON DUPLICATE KEY UPDATE
          counter_value = LAST_INSERT_ID(counter_value + 1)", [
            ":counter_name" => $counter_name,
            ":counter_value" => $lastModelId
        ])->execute();
        return $conn_w->getLastInsertID();
    }


    /**
     * Returns the current value of a named counter.
     *
     * @param Connection Database where the counter resides.
     * @param string Counter name to read.
     * @return int|null Current value, or `null` if the counter does not exist.
     *
     * @task util
     * @throws \yii\db\Exception
     */
    public static function loadCurrentCounterValue(
        Connection $conn_r,
        $counter_name)
    {
        $row = $conn_r->createCommand("SELECT counter_value FROM " . self::COUNTER_TABLE_NAME . " WHERE counter_name=:counter_name", [
            ":counter_name" => $counter_name,
        ])->queryOne();
        if (!$row) {
            return null;
        }
        return (int)$row['counter_value'];
    }


    /**
     * Overwrite a named counter, forcing it to a specific value.
     *
     * If the counter does not exist, it is created.
     *
     * @param Connection $conn_w
     * @param $counter_name
     * @param $counter_value
     * @return void
     *
     * @task util
     * @throws \yii\db\Exception
     */
    public static function overwriteCounterValue(
        Connection $conn_w,
        $counter_name,
        $counter_value)
    {

        $conn_w->createCommand("INSERT INTO " . self::COUNTER_TABLE_NAME . " (counter_name, counter_value) VALUES (:counter_name, :counter_value) ON DUPLICATE KEY UPDATE counter_value = VALUES(counter_value)", [
            ":counter_name" => $counter_name,
            ":counter_value" => $counter_value
        ])->execute();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     * @throws \yii\base\InvalidConfigException
     */
    public static function find()
    {
        return Yii::createObject(PhabricatorQuery::class, [get_called_class()]);
    }

    /* -(  Manging Transactions  )----------------------------------------------- */


    /**
     * Increase transaction stack depth.
     *
     * @return static
     * @throws \yii\db\Exception
     */
    public function openTransaction()
    {
        self::getDb()->openTransaction();
        return $this;
    }


    /**
     * Decrease transaction stack depth, saving work.
     *
     * @return static
     * @throws Exception
     */
    public function saveTransaction()
    {
        self::getDb()->saveTransaction();
        return $this;
    }


    /**
     * Decrease transaction stack depth, discarding work.
     *
     * @return static
     * @throws Exception
     */
    public function killTransaction()
    {
        self::getDb()->killTransaction();
        return $this;
    }


    /**
     * Begins read-locking selected rows with SELECT ... FOR UPDATE, so that
     * other connections can not read them (this is an enormous oversimplification
     * of FOR UPDATE semantics; consult the MySQL documentation for details). To
     * end read locking, call @{method:endReadLocking}. For example:
     *
     *   $beach->openTransaction();
     *     $beach->beginReadLocking();
     *
     *       $beach->reload();
     *       $beach->setGrainsOfSand($beach->getGrainsOfSand() + 1);
     *       $beach->save();
     *
     *     $beach->endReadLocking();
     *   $beach->saveTransaction();
     *
     * @return static
     * @task xaction
     */
    public function beginReadLocking()
    {
        self::getDb()->beginReadLocking();
        return $this;
    }


    /**
     * Ends read-locking that began at an earlier @{method:beginReadLocking} call.
     *
     * @return static
     * @task xaction
     * @throws Exception
     */
    public function endReadLocking()
    {
        self::getDb()->endReadLocking();
        return $this;
    }

    /**
     * Begins write-locking selected rows with SELECT ... LOCK IN SHARE MODE, so
     * that other connections can not update or delete them (this is an
     * oversimplification of LOCK IN SHARE MODE semantics; consult the
     * MySQL documentation for details). To end write locking, call
     * @{method:endWriteLocking}.
     *
     * @return static
     * @task xaction
     */
    public function beginWriteLocking()
    {
        self::getDb()->beginWriteLocking();
        return $this;
    }


    /**
     * Ends write-locking that began at an earlier @{method:beginWriteLocking}
     * call.
     *
     * @return static
     * @task xaction
     * @throws Exception
     */
    public function endWriteLocking()
    {
        self::getDb()->endWriteLocking();
        return $this;
    }

    /**
     * @param $column
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getColumnMaximumByteLength($column)
    {
        $map = self::getTableSchema()->columns;

        if (!isset($map[$column])) {
            throw new Exception(
                Yii::t("app",
                    'Object (of class "{0}") does not have a column "{1}".',
                    [
                        get_class($this),
                        $column
                    ]));
        }

        $data_type = $map[$column];

        return ArrayHelper::getValue($data_type, 'size');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->getAttribute('phid');
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function attributes()
    {
        return ArrayHelper::merge(parent::attributes(), ['phid']);
    }


    /**
     * Reload an object from the database, discarding any changes to persistent
     * properties. This is primarily useful after entering a transaction but
     * before applying changes to an object.
     *
     * @return static
     *
     * @task   load
     * @throws Exception
     * @throws AphrontObjectMissingQueryException
     */
    public function reload()
    {
        if (!$this->getID()) {
            throw new Exception(
                Yii::t("app", "Unable to reload object that hasn't been loaded!"));
        }
        $result = static::findOne($this->getID());
        if (!$result) {
            throw new AphrontObjectMissingQueryException();
        }
        return $this;
    }

    /**
     * Save this object, forcing the query to use REPLACE regardless of object
     * state.
     *
     * @return bool|ActiveRecord
     *
     * @task   save
     * @throws LiskEphemeralObjectException
     * @throws \Throwable
     */
    public function replace()
    {
        $this->isEphemeralCheck();

        if (!$this->validate()) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->isTransactional(self::OP_INSERT)) {
            return $this->replaceInternal();
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $result = $this->replaceInternal();
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }


    /**
     * Inserts an ActiveRecord into DB without considering transaction.
     * @param array $attributes list of attributes that need to be saved. Defaults to `null`,
     * meaning all attributes that are loaded from DB will be saved.
     * @return bool whether the record is inserted successfully.
     * @throws \yii\base\InvalidConfigException
     */
    protected function replaceInternal($attributes = null)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (($primaryKeys = $this->schemaReplace(static::getDb()->schema, static::tableName(), $values)) === false) {
            return false;
        }
        foreach ($primaryKeys as $name => $value) {
            $id = static::getTableSchema()->columns[$name]->phpTypecast($value);
            $this->setAttribute($name, $id);
            $values[$name] = $id;
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    /**
     * Executes the INSERT command, returning primary key values.
     * @param Schema $schema
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     * @return array|false primary key values or false if the command fails
     * @since 2.0.4
     */
    public function schemaReplace($schema, $table, $columns)
    {
        $command = $this->commandReplace($schema->db->createCommand(), $table, $columns);
        if (!$command->execute()) {
            return false;
        }
        $tableSchema = $schema->getTableSchema($table);
        $result = [];
        foreach ($tableSchema->primaryKey as $name) {
            if ($tableSchema->columns[$name]->autoIncrement) {
                $result[$name] = $schema->getLastInsertID($tableSchema->sequenceName);
                break;
            }

            $result[$name] = isset($columns[$name]) ? $columns[$name] : $tableSchema->columns[$name]->defaultValue;
        }

        return $result;
    }

    /**
     * @param Command $command
     * @param $table
     * @param $columns
     * @return mixed
     * @author 陈妙威
     */
    public function commandReplace($command, $table, $columns)
    {
        $params = [];
        $sql = $command->db->getQueryBuilder()->replace($table, $columns, $params);
        return $command->setSql($sql)->bindValues($params);
    }


    /**
     * Make an object read-only.
     *
     * Making an object ephemeral indicates that you will be changing state in
     * such a way that you would never ever want it to be written back to the
     * storage.
     */
    public function makeEphemeral()
    {
        $this->ephemeral = true;
        return $this;
    }

    /**
     * @throws LiskEphemeralObjectException
     * @author 陈妙威
     */
    private function isEphemeralCheck()
    {
        if ($this->ephemeral) {
            throw new LiskEphemeralObjectException();
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function assertAttached($property)
    {
        if ($property === self::ATTACHABLE) {
            throw new PhabricatorDataNotAttachedException($this);
        }
        return $property;
    }

    /**
     * @param $value
     * @param $key
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function assertAttachedKey($value, $key)
    {
        $this->assertAttached($value);
        if (!array_key_exists($key, $value)) {
            throw new PhabricatorDataNotAttachedException($this);
        }
        return $value[$key];
    }

    /**
     * 批量插入的时候，分片插入，保证每次插入的时候不超过1M
     * Break a list of escaped SQL statement fragments (e.g., VALUES lists for
     * INSERT, previously built with @{function:qsprintf}) into chunks which will
     * fit under the MySQL 'max_allowed_packet' limit.
     *
     * If a statement is too large to fit within the limit, it is broken into
     * its own chunk (but might fail when the query executes).
     * @param array $fragments
     * @param null $limit
     * @return array
     */
    public static function chunkSQL(
        array $fragments,
        $limit = null)
    {

        if ($limit === null) {
            // NOTE: Hard-code this at 1MB for now, minus a 10% safety buffer.
            // Eventually we could query MySQL or let the user configure it.
            $limit = (int)((1024 * 1024) * 0.90);
        }

        $result = array();

        $chunk = array();
        $len = 0;
        $glue_len = strlen(', ');
        foreach ($fragments as $fragment) {
            $this_len = strlen(implode($fragment));

            if ($chunk) {
                // Chunks after the first also imply glue.
                $this_len += $glue_len;
            }

            if ($len + $this_len <= $limit) {
                $len += $this_len;
                $chunk[] = $fragment;
            } else {
                if ($chunk) {
                    $result[] = $chunk;
                }
                $len = ($this_len - $glue_len);
                $chunk = array($fragment);
            }
        }

        if ($chunk) {
            $result[] = $chunk;
        }

        return $result;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        try {
            return parent::save($runValidation, $attributeNames);
        } catch (IntegrityException $e) {
            $errno = null;
            $message = $e->getMessage();
            if ($e->errorInfo && isset($e->errorInfo[1])) {
                $errno = $e->errorInfo[1];
            }
            switch ($errno) {
                case 2013: // Connection Dropped
                    throw new AphrontConnectionLostQueryException($message);
                case 2006: // Gone Away
                    $more = \Yii::t("app",
                        'This error may occur if your configured MySQL "wait_timeout" or ' .
                        '"max_allowed_packet" values are too small. This may also indicate ' .
                        'that something used the MySQL "KILL <process>" command to kill ' .
                        'the connection running the query.');
                    throw new AphrontConnectionLostQueryException("{$message}\n\n{$more}");
                case 1213: // Deadlock
                    throw new AphrontDeadlockQueryException($message);
                case 1205: // Lock wait timeout exceeded
                    throw new AphrontLockTimeoutQueryException($message);
                case 1062: // Duplicate Key
                    // NOTE: In some versions of MySQL we get a key name back here, but
                    // older versions just give us a key index ("key 2") so it's not
                    // portable to parse the key out of the error and attach it to the
                    // exception.
                    throw new AphrontDuplicateKeyQueryException($message);
                case 1044: // Access denied to database
                case 1142: // Access denied to table
                case 1143: // Access denied to column
                case 1227: // Access denied (e.g., no SUPER for SHOW SLAVE STATUS).
                    throw new AphrontAccessDeniedQueryException($message);
                case 1045: // Access denied (auth)
                    throw new AphrontInvalidCredentialsQueryException($message);
                case 1146: // No such table
                case 1049: // No such database
                case 1054: // Unknown column "..." in field list
                    throw new AphrontSchemaQueryException($message);
                default:
                    throw $e;
            }
        }
    }
}