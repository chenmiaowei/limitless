<?php

namespace orangins\modules\people\cache;

use orangins\lib\helpers\OranginsUtil;

/**
 * Class PhabricatorUserBadgesCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
final class PhabricatorUserBadgesCacheType extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'badges.award';

    /**
     *
     */
    const KEY_BADGES = 'user.badge.award.v1';

    /**
     *
     */
    const BADGE_COUNT = 2;

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAutoloadKeys()
    {
        return array(
            self::KEY_BADGES,
        );
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function canManageKey($key)
    {
        return ($key === self::KEY_BADGES);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威

     */
    public function getValueFromStorage($value)
    {
        return phutil_json_decode($value);
    }

    /**
     * @param $key
     * @param array $users
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function newValueForUsers($key, array $users)
    {
        return array();

//
//        if (!$users) {
//            return array();
//        }
//
//        $user_phids = mpull($users, 'getPHID');
//
//        $results = array();
//        foreach ($user_phids as $user_phid) {
//            $awards = (new PhabricatorBadgesAwardQuery())
//                ->setViewer($this->getViewer())
//                ->withRecipientPHIDs(array($user_phid))
//                ->withBadgeStatuses(array(PhabricatorBadgesBadge::STATUS_ACTIVE))
//                ->setLimit(self::BADGE_COUNT)
//                ->execute();
//
//            $award_data = array();
//            if ($awards) {
//                foreach ($awards as $award) {
//                    $badge = $award->getBadge();
//                    $award_data[] = array(
//                        'icon' => $badge->getIcon(),
//                        'name' => $badge->getName(),
//                        'quality' => $badge->getQuality(),
//                        'id' => $badge->getID(),
//                    );
//                }
//            }
//            $results[$user_phid] = phutil_json_encode($award_data);
//
//        }
//
//        return $results;
    }

}
