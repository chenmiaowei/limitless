<?php

namespace orangins\lib\infrastructure\daemon\workers;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\time\PhabricatorTime;
use PhutilDaemonOverseerModule;
use PhutilDaemonPool;

/**
 * Class PhabricatorTaskmasterDaemonModule
 * @package orangins\lib\infrastructure\daemon\workers
 * @author 陈妙威
 */
final class PhabricatorTaskmasterDaemonModule
    extends PhutilDaemonOverseerModule
{

    /**
     * @param PhutilDaemonPool $pool
     * @return bool
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function shouldWakePool(PhutilDaemonPool $pool)
    {
        $class = $pool->getPoolDaemonClass();

        if ($class != 'PhabricatorTaskmasterDaemon') {
            return false;
        }

        if ($this->shouldThrottle($class, 1)) {
            return false;
        }

        $table = new PhabricatorWorkerActiveTask();
        $row = $table->getDb()
            ->createCommand("SELECT id FROM " . $table::tableName() . " WHERE lease_owner IS NULL OR lease_expires <= :lease_expires LIMIT 1", [
                ":lease_expires" => PhabricatorTime::getNow()
            ])->queryOne();
        if (!$row) {
            return false;
        }
        return true;
    }

    /**
     * @param array $throttles
     * @return self
     */
    public function setThrottles($throttles)
    {
        $this->throttles = $throttles;
        return $this;
    }
}
