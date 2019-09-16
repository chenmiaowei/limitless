<?php

namespace orangins\modules\conduit\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\modules\conduit\models\PhabricatorConduitMethodCallLog;

/**
 * Class ConduitLogGarbageCollector
 * @package orangins\modules\conduit\garbagecollector
 * @author 陈妙威
 */
final class ConduitLogGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'conduit.logs';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app", 'Conduit Logs');
    }

    /**
     * @return int|void
     * @author 陈妙威
     */
    public function getDefaultRetentionPolicy()
    {
        return phutil_units('180 days in seconds');
    }

    /**
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function collectGarbage()
    {
        $table = new PhabricatorConduitMethodCallLog();

        $connection = $table->getDb();
        $rowCount = $connection->createCommand("DELETE FROM " . PhabricatorConduitMethodCallLog::tableName() . " WHERE created_at < :created_at ORDER BY created_at ASC LIMIT 100", [
            ":created_at" => $this->getGarbageEpoch(),
        ])->execute();
        return ($rowCount == 100);
    }
}
