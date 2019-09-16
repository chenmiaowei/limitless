<?php

namespace orangins\lib\infrastructure\util;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\Connection;
use orangins\lib\infrastructure\log\PhabricatorAccessLog;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\daemon\garbagecollector\PhabricatorDaemonLockLogGarbageCollector;
use orangins\modules\daemon\models\PhabricatorDaemonLockLog;
use PhutilLock;
use PhutilLockException;
use PhutilNumber;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Global, MySQL-backed lock. This is a high-reliability, low-performance
 * global lock.
 *
 * The lock is maintained by using GET_LOCK() in MySQL, and automatically
 * released when the connection terminates. Thus, this lock can safely be used
 * to control access to shared resources without implementing any sort of
 * timeout or override logic: the lock can't normally be stuck in a locked state
 * with no process actually holding the lock.
 *
 * However, acquiring the lock is moderately expensive (several network
 * roundtrips). This makes it unsuitable for tasks where lock performance is
 * important.
 *
 *    $lock = PhabricatorGlobalLock::newLock('example');
 *    $lock->lock();
 *      do_contentious_things();
 *    $lock->unlock();
 *
 * NOTE: This lock is not completely global; it is namespaced to the active
 * storage namespace so that unit tests running in separate table namespaces
 * are isolated from one another.
 *
 * @task construct  Constructing Locks
 * @task impl       Implementation
 */
final class PhabricatorGlobalLock extends PhutilLock
{

    /**
     * @var
     */
    private $parameters;
    /**
     * @var Connection
     */
    private $conn;
    /**
     * @var bool
     */
    private $isExternalConnection = false;
    /**
     * @var
     */
    private $log;
    /**
     * @var
     */
    private $disableLogging;

    /**
     * @var array
     */
    private static $pool = array();


    /* -(  Constructing Locks  )------------------------------------------------- */


    /**
     * @param $name
     * @param array $parameters
     * @return PhabricatorGlobalLock
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public static function newLock($name, $parameters = array())
    {
        $namespace = ActiveRecord::getStorageNamespace();
        $namespace = PhabricatorHash::digestToLength($namespace, 20);

        $parts = array();
        ksort($parameters);
        foreach ($parameters as $key => $parameter) {
            if (!preg_match('/^[a-zA-Z0-9]+\z/', $key)) {
                throw new Exception(
                    \Yii::t("app",
                        'Lock parameter key "{0}" must be alphanumeric.',
                        [
                            $key
                        ]));
            }

            if (!is_scalar($parameter) && !is_null($parameter)) {
                throw new Exception(
                    \Yii::t("app",
                        'Lock parameter for key "{0}" must be a scalar.',
                        [
                            $key
                        ]));
            }

            $value = phutil_json_encode($parameter);
            $parts[] = "{$key}={$value}";
        }
        $parts = implode(', ', $parts);

        $local = "{$name}({$parts})";
        $local = PhabricatorHash::digestToLength($local, 20);

        $full_name = "ph:{$namespace}:{$local}";
        $lock = self::getLock($full_name);
        if (!$lock) {
            $lock = new PhabricatorGlobalLock($full_name);
            self::registerLock($lock);

            $lock->parameters = $parameters;
        }

        return $lock;
    }

    /**
     * Use a specific database connection for locking.
     *
     * By default, `PhabricatorGlobalLock` will lock on the "repository" database
     * (somewhat arbitrarily). In most cases this is fine, but this method can
     * be used to lock on a specific connection.
     *
     * @param Connection $conn
     * @return PhabricatorGlobalLock
     */
    public function useSpecificConnection(Connection $conn)
    {
        $this->conn = $conn;
        $this->isExternalConnection = true;
        return $this;
    }

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableLogging($disable)
    {
        $this->disableLogging = $disable;
        return $this;
    }


    /* -(  Implementation  )----------------------------------------------------- */

    /**
     * @param $wait
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function doLock($wait)
    {
        $conn = $this->conn;

        if (!$conn) {
            // Try to reuse a connection from the connection pool.
            $conn = array_pop(self::$pool);
        }

        if (!$conn) {
            // NOTE: Using the 'repository' database somewhat arbitrarily, mostly
            // because the first client of locks is the repository daemons. We must
            // always use the same database for all locks, but don't access any
            // tables so we could use any valid database. We could build a
            // database-free connection instead, but that's kind of messy and we
            // might forget about it in the future if we vertically partition the
            // application.

            $conn = \Yii::$app->getDb();
        }

        // NOTE: Since MySQL will disconnect us if we're idle for too long, we set
        // the wait_timeout to an enormous value, to allow us to hold the
        // connection open indefinitely (or, at least, for 24 days).
        $max_allowed_timeout = 2147483;

        $conn->createCommand("SET wait_timeout = :wait_timeout", [
            ":wait_timeout" => $max_allowed_timeout
        ])->execute();

        $lock_name = $this->getName();


        $result = $conn->createCommand("SELECT GET_LOCK(:s, :f)", [
            ":s" => $lock_name,
            ":f" => $wait
        ])->queryOne();

        $ok = head($result);
        if (!$ok) {
            throw (new PhutilLockException($lock_name))
                ->setHint($this->newHint($lock_name, $wait));
        }

        $conn->rememberLock($lock_name);

        $this->conn = $conn;

        if ($this->shouldLogLock()) {
            $lock_context = $this->newLockContext();

            $log = (new PhabricatorDaemonLockLog())
                ->setLockName($lock_name)
                ->setLockParameters($this->parameters)
                ->setLockContext($lock_context)
                ->save();

            $this->log = $log;
        }
    }

    /**
     * @author 陈妙威
     */
    protected function doUnlock()
    {
        $lock_name = $this->getName();

        $conn = $this->conn;
        try {
            $result = $this->conn->createCommand('SELECT RELEASE_LOCK(:s)', [
                ":s" => $lock_name
            ])->queryOne();

            $conn->forgetLock($lock_name);
        } catch (Exception $ex) {
            $result = array(null);
        }

        $ok = head($result);
        if (!$ok) {
            // TODO: We could throw here, but then this lock doesn't get marked
            // unlocked and we throw again later when exiting. It also doesn't
            // particularly matter for any current applications. For now, just
            // swallow the error.
        }

        $this->conn = null;
        $this->isExternalConnection = false;

        if (!$this->isExternalConnection) {
            $conn->close();
            self::$pool[] = $conn;
        }

        if ($this->log) {
            $log = $this->log;
            $this->log = null;

            $conn->createCommand("UPDATE " . $log->getTableName() . " SET lockReleased = UNIX_TIMESTAMP() WHERE id = :id", [
                ":id" => $log->getID()
            ])->execute();
        }
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function shouldLogLock()
    {
        if ($this->disableLogging) {
            return false;
        }

        $policy = (new PhabricatorDaemonLockLogGarbageCollector())
            ->getRetentionPolicy();
        if (!$policy) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function newLockContext()
    {
        $context = array(
            'pid' => getmypid(),
            'host' => php_uname('n'),
            'sapi' => php_sapi_name(),
        );

        global $argv;
        if ($argv) {
            $context['argv'] = $argv;
        }

        $access_log = null;

        // TODO: There's currently no cohesive way to get the parameterized access
        // log for the current request across different request types. Web requests
        // have an "AccessLog", SSH requests have an "SSHLog", and other processes
        // (like scripts) have no log. But there's no method to say "give me any
        // log you've got". For now, just test if we have a web request and use the
        // "AccessLog" if we do, since that's the only one we actually read any
        // parameters from.

        // NOTE: "PhabricatorStartup" is only available from web requests, not
        // from CLI scripts.
        if (class_exists('PhabricatorStartup', false)) {
            $access_log = PhabricatorAccessLog::getLog();
        }

        if ($access_log) {
            $controller = $access_log->getData('C');
            if ($controller) {
                $context['controller'] = $controller;
            }

            $method = $access_log->getData('m');
            if ($method) {
                $context['method'] = $method;
            }
        }

        return $context;
    }

    /**
     * @param $lock_name
     * @param $wait
     * @return string
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function newHint($lock_name, $wait)
    {
        if (!$this->shouldLogLock()) {
            return \Yii::t("app",
                'Enable the lock log for more detailed information about ' .
                'which process is holding this lock.');
        }

        $now = PhabricatorTime::getNow();

        // First, look for recent logs. If other processes have been acquiring and
        // releasing this lock while we've been waiting, this is more likely to be
        // a contention/throughput issue than an issue with something hung while
        // holding the lock.
        $limit = 100;
        $logs = (new PhabricatorDaemonLockLog())->loadAllWhere(
            'lockName = %s AND dateCreated >= %d ORDER BY id ASC LIMIT %d',
            $lock_name,
            ($now - $wait),
            $limit);

        if ($logs) {
            if (count($logs) === $limit) {
                return \Yii::t("app",
                    'During the last {0} second(s) spent waiting for the lock, more ' .
                    'than {1} other process(es) acquired it, so this is likely a ' .
                    'bottleneck. Use "bin/lock log --name {2}" to review log activity.',
                    [
                        $wait,
                        $limit,
                        $lock_name
                    ]);
            } else {
                return \Yii::t("app",
                    'During the last {0} second(s) spent waiting for the lock, {1} ' .
                    'other process(es) acquired it, so this is likely a ' .
                    'bottleneck. Use "bin/lock log --name {2}" to review log activity.',
                    [
                        $wait,
                        count($logs),
                        $lock_name
                    ]);
            }
        }

        $last_log = (new PhabricatorDaemonLockLog())->loadOneWhere(
            'lockName = %s ORDER BY id DESC LIMIT 1',
            $lock_name);

        if ($last_log) {
            $info = array();

            $acquired = $last_log->created_at;
            $context = $last_log->getLockContext();

            $process_info = array();

            $pid = ArrayHelper::getValue($context, 'pid');
            if ($pid) {
                $process_info[] = 'pid=' . $pid;
            }

            $host = ArrayHelper::getValue($context, 'host');
            if ($host) {
                $process_info[] = 'host=' . $host;
            }

            $sapi = ArrayHelper::getValue($context, 'sapi');
            if ($sapi) {
                $process_info[] = 'sapi=' . $sapi;
            }

            $argv = ArrayHelper::getValue($context, 'argv');
            if ($argv) {
                $process_info[] = 'argv=' . (string)csprintf('%LR', $argv);
            }

            $controller = ArrayHelper::getValue($context, 'controller');
            if ($controller) {
                $process_info[] = 'controller=' . $controller;
            }

            $method = ArrayHelper::getValue($context, 'method');
            if ($method) {
                $process_info[] = 'method=' . $method;
            }

            $process_info = implode(', ', $process_info);

            $info[] = \Yii::t("app",
                'This lock was most recently acquired by a process ({0}) ' .
                '{1} second(s) ago.',
               [
                   $process_info,
                   $now - $acquired
               ]);

            $released = $last_log->getLockReleased();
            if ($released) {
                $info[] = \Yii::t("app",
                    'This lock was released {0} second(s) ago.',[
                        $now - $released
                    ]);
            } else {
                $info[] = \Yii::t("app", 'There is no record of this lock being released.');
            }

            return implode(' ', $info);
        }

        return \Yii::t("app",
            'Found no records of processes acquiring or releasing this lock.');
    }

}
