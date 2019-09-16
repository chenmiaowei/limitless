<?php

namespace orangins\modules\cache\models;

use orangins\lib\db\ActiveRecord;
use yii\db\Connection;

/**
 * Class PhabricatorCacheDAO
 * @package orangins\modules\cache\models
 * @author 陈妙威
 */
abstract class PhabricatorCacheDAO extends ActiveRecord
{
    /**
     * @return Connection
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return \Yii::$app->get("db");
    }
}
