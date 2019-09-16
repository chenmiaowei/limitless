<?php

namespace orangins\modules\daemon\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\modules\daemon\models\PhabricatorDaemonLockLog;

/**
 * Class PhabricatorDaemonLockLogGarbageCollector
 * @package orangins\lib\infrastructure\garbagecollector
 * @author 陈妙威
 */
final class PhabricatorDaemonLockLogGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'daemon.lock-log';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app",'Lock Logs');
    }

    /**
     * @return int|void
     * @author 陈妙威
     */
    public function getDefaultRetentionPolicy()
    {
        return 0;
    }

    /**
     * @return bool
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function collectGarbage()
    {
        $table = new PhabricatorDaemonLockLog();
        $getAffectedRows = $table
            ->getDb()
            ->createCommand("DELETE FROM " . $table::tableName() . " WHERE created_at < :created_at LIMIT 100", [
                ":created_at" => $this->getGarbageEpoch()
            ])->execute();
        return ($getAffectedRows == 100);
    }

}
