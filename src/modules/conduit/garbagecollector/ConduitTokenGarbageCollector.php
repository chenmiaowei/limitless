<?php

namespace orangins\modules\conduit\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\conduit\models\PhabricatorConduitToken;

/**
 * Class ConduitTokenGarbageCollector
 * @package orangins\modules\conduit\garbagecollector
 * @author 陈妙威
 */
final class ConduitTokenGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'conduit.tokens';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app", 'Conduit Tokens');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticPolicy()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \yii\db\Exception
     */
    protected function collectGarbage()
    {
        $table = new PhabricatorConduitToken();
        $getAffectedRows = $table->getDb()
            ->createCommand("DELETE FROM " . $table::tableName() . " WHERE expires <= :expires ORDER BY created_at ASC LIMIT 100", [
                ":expires" => PhabricatorTime::getNow()
            ])
            ->execute();
        return ($getAffectedRows == 100);
    }

}
