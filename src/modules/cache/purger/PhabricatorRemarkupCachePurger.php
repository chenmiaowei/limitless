<?php

namespace orangins\modules\cache\purger;

use orangins\modules\cache\models\PhabricatorMarkupCache;
use Yii;

/**
 * Class PhabricatorRemarkupCachePurger
 * @package orangins\modules\cache\purger
 * @author 陈妙威
 */
final class PhabricatorRemarkupCachePurger
    extends PhabricatorCachePurger
{

    /**
     *
     */
    const PURGERKEY = 'remarkup';

    /**
     * @return mixed|void
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function purgeCache()
    {

        $tableName = PhabricatorMarkupCache::tableName();
        Yii::$app->getDb()->createCommand("TRUNCATE TABLE {$tableName}")->execute();
    }

}
