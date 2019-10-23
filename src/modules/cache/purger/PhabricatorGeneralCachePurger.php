<?php

namespace orangins\modules\cache\purger;

use orangins\modules\cache\models\PhabricatorCacheGeneral;
use orangins\modules\cache\models\PhabricatorMarkupCache;
use Yii;

/**
 * Class PhabricatorGeneralCachePurger
 * @package orangins\modules\cache\purger
 * @author 陈妙威
 */
final class PhabricatorGeneralCachePurger
    extends PhabricatorCachePurger
{

    /**
     *
     */
    const PURGERKEY = 'general';

    /**
     * @return mixed|void
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function purgeCache()
    {
        $tableName = PhabricatorCacheGeneral::tableName();
        Yii::$app->getDb()->createCommand("TRUNCATE TABLE {$tableName}")->execute();
    }
}
