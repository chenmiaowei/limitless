<?php

namespace orangins\lib\infrastructure\cluster\search;

use orangins\lib\infrastructure\cluster\PhabricatorClusterServiceHealthRecord;
use orangins\lib\OranginsObject;
use orangins\modules\search\fulltextstorage\PhabricatorFulltextStorageEngine;

/**
 * Class PhabricatorSearchHost
 * @package orangins\lib\infrastructure\cluster\search
 * @author 陈妙威
 */
abstract class PhabricatorSearchHost
    extends OranginsObject
{

    /**
     *
     */
    const KEY_REFS = 'cluster.search.refs';
    /**
     *
     */
    const KEY_HEALTH = 'cluster.search.health';

    /**
     * @var PhabricatorFulltextStorageEngine
     */
    protected $engine;
    /**
     * @var
     */
    protected $healthRecord;
    /**
     * @var array
     */
    protected $roles = array();

    /**
     * @var
     */
    protected $disabled;
    /**
     * @var
     */
    protected $host;
    /**
     * @var
     */
    protected $port;

    /**
     *
     */
    const STATUS_OKAY = 'okay';
    /**
     *
     */
    const STATUS_FAIL = 'fail';

    /**
     * PhabricatorSearchHost constructor.
     * @param PhabricatorFulltextStorageEngine $engine
     */
    public function __construct(PhabricatorFulltextStorageEngine $engine)
    {
        $this->engine = $engine;
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
     * @return PhabricatorFulltextStorageEngine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isWritable()
    {
        return $this->hasRole('write');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isReadable()
    {
        return $this->hasRole('read');
    }

    /**
     * @param $role
     * @return bool
     * @author 陈妙威
     */
    public function hasRole($role)
    {
        return isset($this->roles[$role]) && $this->roles[$role] === true;
    }

    /**
     * @param array $roles
     * @return $this
     * @author 陈妙威
     */
    public function setRoles(array $roles)
    {
        foreach ($roles as $role => $val) {
            $this->roles[$role] = $val;
        }
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRoles()
    {
        $roles = array();
        foreach ($this->roles as $key => $val) {
            if ($val) {
                $roles[$key] = $val;
            }
        }
        return $roles;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setPort($value)
    {
        $this->port = $value;
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
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setHost($value)
    {
        $this->host = $value;
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
     * @return string
     * @author 陈妙威
     */
    public function getHealthRecordCacheKey()
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $key = self::KEY_HEALTH;

        return "{$key}({$host}, {$port})";
    }

    /**
     * @return PhabricatorClusterServiceHealthRecord
     * @throws \Exception
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
     * @param $reachable
     * @throws \Exception
     * @author 陈妙威
     */
    public function didHealthCheck($reachable)
    {
        $record = $this->getHealthRecord();
        $should_check = $record->getShouldCheck();

        if ($should_check) {
            $record->didHealthCheck($reachable);
        }
    }

    /**
     * @return string[] Get a list of fields to show in the status overview UI
     */
    abstract public function getStatusViewColumns();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getConnectionStatus();

}
