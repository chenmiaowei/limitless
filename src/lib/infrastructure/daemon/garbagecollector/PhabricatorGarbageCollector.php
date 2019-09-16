<?php

namespace orangins\lib\infrastructure\daemon\garbagecollector;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorGlobalLock;
use orangins\lib\OranginsObject;
use orangins\lib\time\PhabricatorTime;
use PhutilClassMapQuery;
use PhutilLockException;
use PhutilMethodNotImplementedException;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * @task info Getting Collector Information
 * @task collect Collecting Garbage
 */
abstract class PhabricatorGarbageCollector extends OranginsObject
{


    /* -(  Getting Collector Information  )-------------------------------------- */


    /**
     * Get a human readable name for what this collector cleans up, like
     * "User Activity Logs".
     *
     * @return string Human-readable collector name.
     * @task info
     */
    abstract public function getCollectorName();


    /**
     * Specify that the collector has an automatic retention policy and
     * is not configurable.
     *
     * @return bool True if the collector has an automatic retention policy.
     * @task info
     */
    public function hasAutomaticPolicy()
    {
        return false;
    }


    /**
     * Get the default retention policy for this collector.
     *
     * Return the age (in seconds) when resources start getting collected, or
     * `null` to retain resources indefinitely.
     *
     * @return void Lifetime, or `null` for indefinite retention.
     * @throws PhutilMethodNotImplementedException
     * @task info
     */
    public function getDefaultRetentionPolicy()
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * Get the effective retention policy.
     *
     * @return int|null Lifetime, or `null` for indefinite retention.
     * @throws Exception
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @task info
     */
    public function getRetentionPolicy()
    {
        if ($this->hasAutomaticPolicy()) {
            throw new Exception(
                \Yii::t("app",
                    'Can not get retention policy of collector with automatic ' .
                    'policy.'));
        }

        $config = PhabricatorEnv::getEnvConfig('phd.garbage-collection');
        $const = $this->getCollectorConstant();

        return ArrayHelper::getValue($config, $const, $this->getDefaultRetentionPolicy());
    }


    /**
     * Get a unique string constant identifying this collector.
     *
     * @return string Collector constant.
     * @throws Exception
     * @throws \ReflectionException
     * @task info
     */
    final public function getCollectorConstant()
    {
        return $this->getPhobjectClassConstant('COLLECTORCONST', 64);
    }


    /* -(  Collecting Garbage  )------------------------------------------------- */


    /**
     * Run the collector.
     *
     * @return bool True if there is more garbage to collect.
     * @task collect
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilMethodNotImplementedException
     */
    final public function runCollector()
    {
        // Don't do anything if this collector is configured with an indefinite
        // retention policy.
        if (!$this->hasAutomaticPolicy()) {
            $policy = $this->getRetentionPolicy();
            if (!$policy) {
                return false;
            }
        }

        // Hold a lock while performing collection to avoid racing other daemons
        // running the same collectors.
        $params = array(
            'collector' => $this->getCollectorConstant(),
        );
        $lock = PhabricatorGlobalLock::newLock('gc', $params);

        try {
            $lock->lock(5);
        } catch (PhutilLockException $ex) {
            return false;
        }

        try {
            $result = $this->collectGarbage();
        } catch (Exception $ex) {
            $lock->unlock();
            throw $ex;
        }

        $lock->unlock();

        return $result;
    }


    /**
     * Collect garbage from whatever source this GC handles.
     *
     * @return bool True if there is more garbage to collect.
     * @task collect
     */
    abstract protected function collectGarbage();


    /**
     * Get the most recent epoch timestamp that is considered garbage.
     *
     * Records older than this should be collected.
     *
     * @return int Most recent garbage timestamp.
     * @throws Exception
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @task collect
     */
    final protected function getGarbageEpoch()
    {
        if ($this->hasAutomaticPolicy()) {
            throw new Exception(
                \Yii::t("app",
                    'Can not get garbage epoch for a collector with an automatic ' .
                    'collection policy.'));
        }

        $ttl = $this->getRetentionPolicy();
        if (!$ttl) {
            throw new Exception(
                \Yii::t("app",
                    'Can not get garbage epoch for a collector with an indefinite ' .
                    'retention policy.'));
        }

        return (PhabricatorTime::getNow() - $ttl);
    }


    /**
     * Load all of the available garbage collectors.
     *
     * @return PhabricatorGarbageCollector[] Garbage collectors.
     * @task collect
     */
    final public static function getAllCollectors()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getCollectorConstant')
            ->execute();
    }

}
