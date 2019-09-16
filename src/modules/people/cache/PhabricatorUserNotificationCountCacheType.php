<?php

namespace orangins\modules\people\cache;

use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use yii\db\Query;

/**
 * Class PhabricatorUserNotificationCountCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
final class PhabricatorUserNotificationCountCacheType
    extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'notification.count';

    /**
     *
     */
    const KEY_COUNT = 'user.notification.count.v1';

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAutoloadKeys()
    {
        return array(
            self::KEY_COUNT,
        );
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function canManageKey($key)
    {
        return ($key === self::KEY_COUNT);
    }

    /**
     * @param $value
     * @return int|mixed
     * @author 陈妙威
     */
    public function getValueFromStorage($value)
    {
        return (int)$value;
    }

    /**
     * @param $key
     * @param array $users
     * @return array
     * @author 陈妙威
     */
    public function newValueForUsers($key, array $users)
    {
        if (!$users) {
            return array();
        }

        $user_phids = mpull($users, 'getPHID');
        $table = new PhabricatorFeedStoryNotification();

        $rows =(new Query())
            ->select(['user_phid', 'COUNT(*) N'])
            ->from($table::tableName())
            ->andWhere([
                'IN', 'user_phid', $user_phids
            ])
            ->andWhere([
                'has_viewed' => 0
            ])
            ->groupBy('user_phid')
            ->all();
        $empty = array_fill_keys($user_phids, 0);
        return ipull($rows, 'N', 'userPHID') + $empty;
    }
}
