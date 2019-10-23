<?php

namespace orangins\modules\cache\purger;

use orangins\modules\people\models\PhabricatorUserCache;
use Yii;

/**
 * Class PhabricatorUserCachePurger
 * @package orangins\modules\cache\purger
 * @author 陈妙威
 */
final class PhabricatorUserCachePurger
    extends PhabricatorCachePurger
{

    /**
     *
     */
    const PURGERKEY = 'user';

    /**
     * @return mixed|void
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function purgeCache()
    {
        $tableName = PhabricatorUserCache::tableName();
        Yii::$app->getDb()->createCommand("TRUNCATE TABLE {$tableName}")->execute();
    }
}
