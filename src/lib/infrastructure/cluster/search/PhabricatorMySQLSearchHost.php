<?php

namespace orangins\lib\infrastructure\cluster\search;

use orangins\lib\infrastructure\cluster\PhabricatorClusterServiceHealthRecord;
use orangins\lib\infrastructure\cluster\PhabricatorDatabaseRef;

/**
 * Class PhabricatorMySQLSearchHost
 * @package orangins\lib\infrastructure\cluster\search
 * @author 陈妙威
 */
final class PhabricatorMySQLSearchHost
    extends PhabricatorSearchHost
{

    /**
     * @param $config
     * @return $this
     * @author 陈妙威
     */
    public function setConfig($config)
    {
        $this->setRoles(idx($config, 'roles', array('read' => true, 'write' => true)));
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        return 'MySQL';
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getStatusViewColumns()
    {
        return array(
            pht('Protocol') => 'mysql',
            pht('Roles') => implode(', ', array_keys($this->getRoles())),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProtocol()
    {
        return 'mysql';
    }

    /**
     * @return PhabricatorClusterServiceHealthRecord
     * @author 陈妙威
     */
    public function getHealthRecord()
    {
        if (!$this->healthRecord) {
            $ref = PhabricatorDatabaseRef::getMasterDatabaseRefForApplication(
                'search');
            $this->healthRecord = $ref->getHealthRecord();
        }
        return $this->healthRecord;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConnectionStatus()
    {
        PhabricatorDatabaseRef::queryAll();
        $ref = PhabricatorDatabaseRef::getMasterDatabaseRefForApplication('search');
        $status = $ref->getConnectionStatus();
        return $status;
    }

}
