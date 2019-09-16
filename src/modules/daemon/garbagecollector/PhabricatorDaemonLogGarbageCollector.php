<?php

namespace orangins\modules\daemon\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\modules\daemon\models\PhabricatorDaemonLog;

/**
 * Class PhabricatorDaemonLogGarbageCollector
 * @package orangins\lib\infrastructure\garbagecollector
 * @author 陈妙威
 */
final class PhabricatorDaemonLogGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'daemon.logs';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app",'Daemon Logs');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getDefaultRetentionPolicy()
    {
        return phutil_units('7 days in seconds');
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
        $table = new PhabricatorDaemonLog();
        $getAffectedRows = $table->getDb()
            ->createCommand("DELETE FROM " . $table::tableName() . " WHERE updated_at < :updated_at LIMIT 100", [
                ":updated_at" => $this->getGarbageEpoch()
            ])
            ->execute();
        return ($getAffectedRows == 100);
    }
}
