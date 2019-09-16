<?php

namespace orangins\modules\daemon\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\modules\daemon\models\PhabricatorDaemonLogEvent;

/**
 * Class PhabricatorDaemonLogEventGarbageCollector
 * @package orangins\lib\infrastructure\garbagecollector
 * @author 陈妙威
 */
final class PhabricatorDaemonLogEventGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'daemon.processes';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app",'Daemon Processes');
    }

    /**
     * @return int|void
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
        $table = new PhabricatorDaemonLogEvent();
        $getAffectedRows = $table->getDb()
            ->createCommand("DELETE FROM " . $table::tableName() . " WHERE epoch < :epoch LIMIT 100", [
                ":epoch" => $this->getGarbageEpoch()
            ])
            ->execute();
        return ($getAffectedRows == 100);
    }
}
