<?php

namespace orangins\lib\infrastructure\cluster;

use AphrontAccessDeniedQueryException;
use AphrontDatabaseConnection;
use AphrontInvalidCredentialsQueryException;
use AphrontMySQLDatabaseConnection;
use AphrontMySQLiDatabaseConnection;
use AphrontQueryException;
use AphrontSchemaQueryException;
use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\modules\cache\PhabricatorCaches;
use PhutilOpaqueEnvelope;

/**
 * Class PhabricatorDatabaseRef
 * @package orangins\lib\infrastructure\cluster
 * @author 陈妙威
 */
final class PhabricatorDatabaseRef
    extends OranginsObject
{

    /**
     *
     */
    const STATUS_OKAY = 'okay';
    /**
     *
     */
    const STATUS_FAIL = 'fail';
    /**
     *
     */
    const STATUS_AUTH = 'auth';
    /**
     *
     */
    const STATUS_REPLICATION_CLIENT = 'replication-client';

    /**
     *
     */
    const REPLICATION_OKAY = 'okay';
    /**
     *
     */
    const REPLICATION_MASTER_REPLICA = 'master-replica';
    /**
     *
     */
    const REPLICATION_REPLICA_NONE = 'replica-none';
    /**
     *
     */
    const REPLICATION_SLOW = 'replica-slow';
    /**
     *
     */
    const REPLICATION_NOT_REPLICATING = 'not-replicating';

    /**
     *
     */
    const KEY_HEALTH = 'cluster.db.health';
    /**
     *
     */
    const KEY_REFS = 'cluster.db.refs';
    /**
     *
     */
    const KEY_INDIVIDUAL = 'cluster.db.individual';

    /**
     * @var
     */
    private $host;
    /**
     * @var
     */
    private $port;
    /**
     * @var
     */
    private $user;
    /**
     * @var
     */
    private $pass;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $isMaster;
    /**
     * @var
     */
    private $isIndividual;

    /**
     * @var
     */
    private $connectionLatency;
    /**
     * @var
     */
    private $connectionStatus;
    /**
     * @var
     */
    private $connectionMessage;
    /**
     * @var
     */
    private $connectionException;

    /**
     * @var
     */
    private $replicaStatus;
    /**
     * @var
     */
    private $replicaMessage;
    /**
     * @var
     */
    private $replicaDelay;

    /**
     * @var
     */
    private $healthRecord;
    /**
     * @var
     */
    private $didFailToConnect;

    /**
     * @var
     */
    private $isDefaultPartition;
    /**
     * @var array
     */
    private $applicationMap = array();
    /**
     * @var
     */
    private $masterRef;
    /**
     * @var array
     */
    private $replicaRefs = array();
    /**
     * @var
     */
    private $usePersistentConnections;

    /**
     * @param $host
     * @return $this
     * @author 陈妙威
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param $port
     * @return $this
     * @author 陈妙威
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param PhutilOpaqueEnvelope $pass
     * @return $this
     * @author 陈妙威
     */
    public function setPass(PhutilOpaqueEnvelope $pass)
    {
        $this->pass = $pass;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param $is_master
     * @return $this
     * @author 陈妙威
     */
    public function setIsMaster($is_master)
    {
        $this->isMaster = $is_master;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsMaster()
    {
        return $this->isMaster;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param $connection_latency
     * @return $this
     * @author 陈妙威
     */
    public function setConnectionLatency($connection_latency)
    {
        $this->connectionLatency = $connection_latency;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConnectionLatency()
    {
        return $this->connectionLatency;
    }

    /**
     * @param $connection_status
     * @return $this
     * @author 陈妙威
     */
    public function setConnectionStatus($connection_status)
    {
        $this->connectionStatus = $connection_status;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConnectionStatus()
    {
        if ($this->connectionStatus === null) {
            throw new PhutilInvalidStateException('queryAll');
        }

        return $this->connectionStatus;
    }

    /**
     * @param $connection_message
     * @return $this
     * @author 陈妙威
     */
    public function setConnectionMessage($connection_message)
    {
        $this->connectionMessage = $connection_message;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConnectionMessage()
    {
        return $this->connectionMessage;
    }

    /**
     * @param $replica_status
     * @return $this
     * @author 陈妙威
     */
    public function setReplicaStatus($replica_status)
    {
        $this->replicaStatus = $replica_status;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReplicaStatus()
    {
        return $this->replicaStatus;
    }

    /**
     * @param $replica_message
     * @return $this
     * @author 陈妙威
     */
    public function setReplicaMessage($replica_message)
    {
        $this->replicaMessage = $replica_message;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReplicaMessage()
    {
        return $this->replicaMessage;
    }

    /**
     * @param $replica_delay
     * @return $this
     * @author 陈妙威
     */
    public function setReplicaDelay($replica_delay)
    {
        $this->replicaDelay = $replica_delay;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReplicaDelay()
    {
        return $this->replicaDelay;
    }

    /**
     * @param $is_individual
     * @return $this
     * @author 陈妙威
     */
    public function setIsIndividual($is_individual)
    {
        $this->isIndividual = $is_individual;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsIndividual()
    {
        return $this->isIndividual;
    }

    /**
     * @param $is_default_partition
     * @return $this
     * @author 陈妙威
     */
    public function setIsDefaultPartition($is_default_partition)
    {
        $this->isDefaultPartition = $is_default_partition;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsDefaultPartition()
    {
        return $this->isDefaultPartition;
    }

    /**
     * @param $use_persistent_connections
     * @return $this
     * @author 陈妙威
     */
    public function setUsePersistentConnections($use_persistent_connections)
    {
        $this->usePersistentConnections = $use_persistent_connections;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUsePersistentConnections()
    {
        return $this->usePersistentConnections;
    }

    /**
     * @param array $application_map
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationMap(array $application_map)
    {
        $this->applicationMap = $application_map;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getApplicationMap()
    {
        return $this->applicationMap;
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getPartitionStateForCommit()
    {
        $state = PhabricatorEnv::getEnvConfig('cluster.databases');
        foreach ($state as $key => $value) {
            // Don't store passwords, since we don't care if they differ and
            // users may find it surprising.
            unset($state[$key]['pass']);
        }

        return phutil_json_encode($state);
    }

    /**
     * @param PhabricatorDatabaseRef $master_ref
     * @return $this
     * @author 陈妙威
     */
    public function setMasterRef(PhabricatorDatabaseRef $master_ref)
    {
        $this->masterRef = $master_ref;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMasterRef()
    {
        return $this->masterRef;
    }

    /**
     * @param PhabricatorDatabaseRef $replica_ref
     * @return $this
     * @author 陈妙威
     */
    public function addReplicaRef(PhabricatorDatabaseRef $replica_ref)
    {
        $this->replicaRefs[] = $replica_ref;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getReplicaRefs()
    {
        return $this->replicaRefs;
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getRefKey()
    {
        $host = $this->getHost();

        $port = $this->getPort();
        if (strlen($port)) {
            return "{$host}:{$port}";
        }

        return $host;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getConnectionStatusMap()
    {
        return array(
            self::STATUS_OKAY => array(
                'icon' => 'fa-exchange',
                'color' => 'green',
                'label' => pht('Okay'),
            ),
            self::STATUS_FAIL => array(
                'icon' => 'fa-times',
                'color' => 'red',
                'label' => pht('Failed'),
            ),
            self::STATUS_AUTH => array(
                'icon' => 'fa-key',
                'color' => 'red',
                'label' => pht('Invalid Credentials'),
            ),
            self::STATUS_REPLICATION_CLIENT => array(
                'icon' => 'fa-eye-slash',
                'color' => 'yellow',
                'label' => pht('Missing Permission'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getReplicaStatusMap()
    {
        return array(
            self::REPLICATION_OKAY => array(
                'icon' => 'fa-download',
                'color' => 'green',
                'label' => pht('Okay'),
            ),
            self::REPLICATION_MASTER_REPLICA => array(
                'icon' => 'fa-database',
                'color' => 'red',
                'label' => pht('Replicating Master'),
            ),
            self::REPLICATION_REPLICA_NONE => array(
                'icon' => 'fa-download',
                'color' => 'red',
                'label' => pht('Not A Replica'),
            ),
            self::REPLICATION_SLOW => array(
                'icon' => 'fa-hourglass',
                'color' => 'red',
                'label' => pht('Slow Replication'),
            ),
            self::REPLICATION_NOT_REPLICATING => array(
                'icon' => 'fa-exclamation-triangle',
                'color' => 'red',
                'label' => pht('Not Replicating'),
            ),
        );
    }

    /**
     * @return object
     * @throws Exception
     * @author 陈妙威
     */
    public static function getClusterRefs()
    {
        $cache = PhabricatorCaches::getRequestCache();

        $refs = $cache->getKey(self::KEY_REFS);
        if (!$refs) {
            $refs = self::newRefs();
            $cache->setKey(self::KEY_REFS, $refs);
        }

        return $refs;
    }

    /**
     * @return object
     * @throws Exception
     * @author 陈妙威
     */
    public static function getLiveIndividualRef()
    {
        $cache = PhabricatorCaches::getRequestCache();

        $ref = $cache->getKey(self::KEY_INDIVIDUAL);
        if (!$ref) {
            $ref = self::newIndividualRef();
            $cache->setKey(self::KEY_INDIVIDUAL, $ref);
        }

        return $ref;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function newRefs()
    {
        $default_port = PhabricatorEnv::getEnvConfig('mysql.port');
        $default_port = nonempty($default_port, 3306);

        $default_user = PhabricatorEnv::getEnvConfig('mysql.user');

        $default_pass = PhabricatorEnv::getEnvConfig('mysql.pass');
        $default_pass = new PhutilOpaqueEnvelope($default_pass);

        $config = PhabricatorEnv::getEnvConfig('cluster.databases');

        return id(new PhabricatorDatabaseRefParser())
            ->setDefaultPort($default_port)
            ->setDefaultUser($default_user)
            ->setDefaultPass($default_pass)
            ->newRefs($config);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function queryAll()
    {
        $refs = self::getActiveDatabaseRefs();
        return self::queryRefs($refs);
    }

    /**
     * @param array $refs
     * @return array
     * @author 陈妙威
     */
    private static function queryRefs(array $refs)
    {
        foreach ($refs as $ref) {
            $conn = $ref->newManagementConnection();

            $t_start = microtime(true);
            $replica_status = false;
            try {
                $replica_status = queryfx_one($conn, 'SHOW SLAVE STATUS');
                $ref->setConnectionStatus(self::STATUS_OKAY);
            } catch (AphrontAccessDeniedQueryException $ex) {
                $ref->setConnectionStatus(self::STATUS_REPLICATION_CLIENT);
                $ref->setConnectionMessage(
                    pht(
                        'No permission to run "SHOW SLAVE STATUS". Grant this user ' .
                        '"REPLICATION CLIENT" permission to allow Phabricator to ' .
                        'monitor replica health.'));
            } catch (AphrontInvalidCredentialsQueryException $ex) {
                $ref->setConnectionStatus(self::STATUS_AUTH);
                $ref->setConnectionMessage($ex->getMessage());
            } catch (AphrontQueryException $ex) {
                $ref->setConnectionStatus(self::STATUS_FAIL);

                $class = get_class($ex);
                $message = $ex->getMessage();
                $ref->setConnectionMessage(
                    pht(
                        '%s: %s',
                        get_class($ex),
                        $ex->getMessage()));
            }
            $t_end = microtime(true);
            $ref->setConnectionLatency($t_end - $t_start);

            if ($replica_status !== false) {
                $is_replica = (bool)$replica_status;
                if ($ref->getIsMaster() && $is_replica) {
                    $ref->setReplicaStatus(self::REPLICATION_MASTER_REPLICA);
                    $ref->setReplicaMessage(
                        pht(
                            'This host has a "master" role, but is replicating data from ' .
                            'another host ("%s")!',
                            idx($replica_status, 'Master_Host')));
                } else if (!$ref->getIsMaster() && !$is_replica) {
                    $ref->setReplicaStatus(self::REPLICATION_REPLICA_NONE);
                    $ref->setReplicaMessage(
                        pht(
                            'This host has a "replica" role, but is not replicating data ' .
                            'from a master (no output from "SHOW SLAVE STATUS").'));
                } else {
                    $ref->setReplicaStatus(self::REPLICATION_OKAY);
                }

                if ($is_replica) {
                    $latency = idx($replica_status, 'Seconds_Behind_Master');
                    if (!strlen($latency)) {
                        $ref->setReplicaStatus(self::REPLICATION_NOT_REPLICATING);
                    } else {
                        $latency = (int)$latency;
                        $ref->setReplicaDelay($latency);
                        if ($latency > 30) {
                            $ref->setReplicaStatus(self::REPLICATION_SLOW);
                            $ref->setReplicaMessage(
                                pht(
                                    'This replica is lagging far behind the master. Data is at ' .
                                    'risk!'));
                        }
                    }
                }
            }
        }

        return $refs;
    }

    /**
     * @return AphrontMySQLiDatabaseConnection|AphrontMySQLDatabaseConnection
     * @throws Exception
     * @author 陈妙威
     */
    public function newManagementConnection()
    {
        return $this->newConnection(
            array(
                'retries' => 0,
                'timeout' => 2,
            ));
    }

    /**
     * @param $database
     * @return AphrontMySQLiDatabaseConnection|AphrontMySQLDatabaseConnection
     * @author 陈妙威
     * @throws Exception
     */
    public function newApplicationConnection($database)
    {
        return $this->newConnection(
            array(
                'database' => $database,
            ));
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isSevered()
    {
        // If we only have an individual database, never sever our connection to
        // it, at least for now. It's possible that using the same severing rules
        // might eventually make sense to help alleviate load-related failures,
        // but we should wait for all the cluster stuff to stabilize first.
        if ($this->getIsIndividual()) {
            return false;
        }

        if ($this->didFailToConnect) {
            return true;
        }

        $record = $this->getHealthRecord();
        $is_healthy = $record->getIsHealthy();
        if (!$is_healthy) {
            return true;
        }

        return false;
    }

    /**
     * @param AphrontDatabaseConnection $connection
     * @return bool
     * @throws AphrontSchemaQueryException
     * @throws Exception
     * @author 陈妙威
     */
    public function isReachable(AphrontDatabaseConnection $connection)
    {
        $record = $this->getHealthRecord();
        $should_check = $record->getShouldCheck();

        if ($this->isSevered() && !$should_check) {
            return false;
        }

        $this->connectionException = null;
        try {
            $connection->openConnection();
            $reachable = true;
        } catch (AphrontSchemaQueryException $ex) {
            // We get one of these if the database we're trying to select does not
            // exist. In this case, just re-throw the exception. This is expected
            // during first-time setup, when databases like "config" will not exist
            // yet.
            throw $ex;
        } catch (Exception $ex) {
            $this->connectionException = $ex;
            $reachable = false;
        }

        if ($should_check) {
            $record->didHealthCheck($reachable);
        }

        if (!$reachable) {
            $this->didFailToConnect = true;
        }

        return $reachable;
    }

    /**
     * @return $this
     * @throws AphrontSchemaQueryException
     * @throws Exception
     * @author 陈妙威
     */
    public function checkHealth()
    {
        $health = $this->getHealthRecord();

        $should_check = $health->getShouldCheck();
        if ($should_check) {
            // This does an implicit health update.
            $connection = $this->newManagementConnection();
            $this->isReachable($connection);
        }

        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getHealthRecordCacheKey()
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $key = self::KEY_HEALTH;

        return "{$key}({$host}, {$port})";
    }

    /**
     * @return PhabricatorClusterServiceHealthRecord
     * @throws Exception
     * @author 陈妙威
     */
    public function getHealthRecord()
    {
        if (!$this->healthRecord) {
            $this->healthRecord = new PhabricatorClusterServiceHealthRecord(
                $this->getHealthRecordCacheKey());
        }
        return $this->healthRecord;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConnectionException()
    {
        return $this->connectionException;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function getActiveDatabaseRefs()
    {
        $refs = array();

        foreach (self::getMasterDatabaseRefs() as $ref) {
            $refs[] = $ref;
        }

        foreach (self::getReplicaDatabaseRefs() as $ref) {
            $refs[] = $ref;
        }

        return $refs;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function getAllMasterDatabaseRefs()
    {
        $refs = self::getClusterRefs();

        if (!$refs) {
            return array(self::getLiveIndividualRef());
        }

        $masters = array();
        foreach ($refs as $ref) {
            if ($ref->getIsMaster()) {
                $masters[] = $ref;
            }
        }

        return $masters;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getMasterDatabaseRefs()
    {
        $refs = self::getAllMasterDatabaseRefs();
        return self::getEnabledRefs($refs);
    }

    /**
     * @param $database
     * @return bool
     * @author 陈妙威
     */
    public function isApplicationHost($database)
    {
        return isset($this->applicationMap[$database]);
    }

    /**
     * @param $key
     * @return mixed|null|object
     * @author 陈妙威
     * @throws Exception
     */
    public function loadRawMySQLConfigValue($key)
    {
        $conn = $this->newManagementConnection();

        try {
            $value = queryfx_one($conn, 'SELECT @@%C', $key);

            // NOTE: Although MySQL allows us to escape configuration values as if
            // they are column names, the escaping is included in the column name
            // of the return value: if we select "@@`x`", we get back a column named
            // "@@`x`", not "@@x" as we might expect.
            $value = head($value);

        } catch (AphrontQueryException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * @param $application
     * @return mixed|object
     * @author 陈妙威
     */
    public static function getMasterDatabaseRefForApplication($application)
    {
        $masters = self::getMasterDatabaseRefs();

        $application_master = null;
        $default_master = null;
        foreach ($masters as $master) {
            if ($master->isApplicationHost($application)) {
                $application_master = $master;
                break;
            }
            if ($master->getIsDefaultPartition()) {
                $default_master = $master;
            }
        }

        if ($application_master) {
            $masters = array($application_master);
        } else if ($default_master) {
            $masters = array($default_master);
        } else {
            $masters = array();
        }

        $masters = self::getEnabledRefs($masters);
        $master = head($masters);

        return $master;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function newIndividualRef()
    {
        $default_user = PhabricatorEnv::getEnvConfig('mysql.user');
        $default_pass = new PhutilOpaqueEnvelope(
            PhabricatorEnv::getEnvConfig('mysql.pass'));
        $default_host = PhabricatorEnv::getEnvConfig('mysql.host');
        $default_port = PhabricatorEnv::getEnvConfig('mysql.port');

        return id(new self())
            ->setUser($default_user)
            ->setPass($default_pass)
            ->setHost($default_host)
            ->setPort($default_port)
            ->setIsIndividual(true)
            ->setIsMaster(true)
            ->setIsDefaultPartition(true)
            ->setUsePersistentConnections(false);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function getAllReplicaDatabaseRefs()
    {
        $refs = self::getClusterRefs();

        if (!$refs) {
            return array();
        }

        $replicas = array();
        foreach ($refs as $ref) {
            if ($ref->getIsMaster()) {
                continue;
            }

            $replicas[] = $ref;
        }

        return $replicas;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function getReplicaDatabaseRefs()
    {
        $refs = self::getAllReplicaDatabaseRefs();
        return self::getEnabledRefs($refs);
    }

    /**
     * @param array $refs
     * @return array
     * @author 陈妙威
     */
    private static function getEnabledRefs(array $refs)
    {
        foreach ($refs as $key => $ref) {
            if ($ref->getDisabled()) {
                unset($refs[$key]);
            }
        }
        return $refs;
    }

    /**
     * @param $application
     * @return object
     * @author 陈妙威
     * @throws Exception
     */
    public static function getReplicaDatabaseRefForApplication($application)
    {
        $replicas = self::getReplicaDatabaseRefs();

        $application_replicas = array();
        $default_replicas = array();
        foreach ($replicas as $replica) {
            $master = $replica->getMasterRef();

            if ($master->isApplicationHost($application)) {
                $application_replicas[] = $replica;
            }

            if ($master->getIsDefaultPartition()) {
                $default_replicas[] = $replica;
            }
        }

        if ($application_replicas) {
            $replicas = $application_replicas;
        } else {
            $replicas = $default_replicas;
        }

        $replicas = self::getEnabledRefs($replicas);

        // TODO: We may have multiple replicas to choose from, and could make
        // more of an effort to pick the "best" one here instead of always
        // picking the first one. Once we've picked one, we should try to use
        // the same replica for the rest of the request, though.

        return head($replicas);
    }

    /**
     * @param array $options
     * @return AphrontMySQLiDatabaseConnection|AphrontMySQLDatabaseConnection
     * @throws Exception
     * @author 陈妙威
     */
    private function newConnection(array $options)
    {
        // If we believe the database is unhealthy, don't spend as much time
        // trying to connect to it, since it's likely to continue to fail and
        // hammering it can only make the problem worse.
        $record = $this->getHealthRecord();
        if ($record->getIsHealthy()) {
            $default_retries = 3;
            $default_timeout = 10;
        } else {
            $default_retries = 0;
            $default_timeout = 2;
        }

        $spec = $options + array(
                'user' => $this->getUser(),
                'pass' => $this->getPass(),
                'host' => $this->getHost(),
                'port' => $this->getPort(),
                'database' => null,
                'retries' => $default_retries,
                'timeout' => $default_timeout,
                'persistent' => $this->getUsePersistentConnections(),
            );

        $is_cli = (php_sapi_name() == 'cli');

        $use_persistent = false;
        if (!empty($spec['persistent']) && !$is_cli) {
            $use_persistent = true;
        }
        unset($spec['persistent']);

        $connection = self::newRawConnection($spec);

        // If configured, use persistent connections. See T11672 for details.
        if ($use_persistent) {
            $connection->setPersistent($use_persistent);
        }

        // Unless this is a script running from the CLI, prevent any query from
        // running for more than 30 seconds. See T10849 for details.
        if (!$is_cli) {
            $connection->setQueryTimeout(30);
        }

        return $connection;
    }

    /**
     * @param array $options
     * @return AphrontMySQLiDatabaseConnection|AphrontMySQLDatabaseConnection
     * @author 陈妙威
     */
    public static function newRawConnection(array $options)
    {
        if (extension_loaded('mysqli')) {
            return new AphrontMySQLiDatabaseConnection($options);
        } else {
            return new AphrontMySQLDatabaseConnection($options);
        }
    }

}
