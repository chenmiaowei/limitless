<?php

namespace orangins\modules\people\cache;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\conpherence\models\ConpherenceParticipant;

/**
 * Class PhabricatorUserMessageCountCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
final class PhabricatorUserMessageCountCacheType
    extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'message.count';

    /**
     *
     */
    const KEY_COUNT = 'user.message.count.v1';

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

        $unread = ConpherenceParticipant::countFind()
            ->withParticipantPHIDs($user_phids)
            ->withUnread(true)
            ->execute();

        $empty = array_fill_keys($user_phids, 0);
        return $unread + $empty;
    }
}
