<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/31
 * Time: 2:36 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\cache\PhabricatorUserBadgesCacheType;
use orangins\modules\people\cache\PhabricatorUserCacheType;
use orangins\modules\people\cache\PhabricatorUserPreferencesCacheType;
use orangins\modules\people\cache\PhabricatorUserProfileImageCacheType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\people\models\UserNametoken;
use orangins\modules\people\models\UserProfiles;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPeopleQuery
 * @package orangins\modules\people\query
 * @author 陈妙威
 */
class PhabricatorPeopleQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    public $isMerchant;
    /**
     * @var
     */
    public $isManager;
    /**
     * @var
     */
    private $usernames;
    /**
     * @var
     */
    private $realnames;
    /**
     * @var
     */
    private $emails;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $ids;

    /**
     * @var
     */
    private $rangeMin;

    /**
     * @var
     */
    private $rangeMax;

    /**
     * @var
     */
    private $dateCreatedAfter;
    /**
     * @var
     */
    private $dateCreatedBefore;
    /**
     * @var
     */
    private $isAdmin;
    /**
     * @var
     */
    private $isSystemAgent;
    /**
     * @var
     */
    private $isMailingList;
    /**
     * @var
     */
    private $isDisabled;
    /**
     * @var
     */
    private $isApproved;
    /**
     * @var
     */
    private $nameLike;
    /**
     * @var
     */
    private $nameTokens;
    /**
     * @var
     */
    private $namePrefixes;
    /**
     * @var
     */
    private $isEnrolledInMultiFactor;

    /**
     * @var
     */
    private $needPrimaryEmail;
    /**
     * @var
     */
    private $needProfile;
    /**
     * @var
     */
    private $needProfileImage;
    /**
     * @var
     */
    private $needAvailability;
    /**
     * @var
     */
    private $needBadgeAwards;
    /**
     * @var array
     */
    private $cacheKeys = array();

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $emails
     * @return $this
     * @author 陈妙威
     */
    public function withEmails(array $emails)
    {
        $this->emails = $emails;
        return $this;
    }

    /**
     * @param array $realnames
     * @return $this
     * @author 陈妙威
     */
    public function withRealnames(array $realnames)
    {
        $this->realnames = $realnames;
        return $this;
    }

    /**
     * @param array $usernames
     * @return $this
     * @author 陈妙威
     */
    public function withUsernames(array $usernames)
    {
        $this->usernames = $usernames;
        return $this;
    }

    /**
     * @param $date_created_before
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBefore($date_created_before)
    {
        $this->dateCreatedBefore = $date_created_before;
        return $this;
    }

    /**
     * @param $date_created_after
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedAfter($date_created_after)
    {
        $this->dateCreatedAfter = $date_created_after;
        return $this;
    }

    /**
     * @param $admin
     * @return $this
     * @author 陈妙威
     */
    public function withIsAdmin($admin)
    {
        $this->isAdmin = $admin;
        return $this;
    }

    /**
     * @param $range_min
     * @param $range_max
     * @return $this
     * @author 陈妙威
     */
    public function withEpochInRange($range_min, $range_max)
    {
        $this->rangeMin = $range_min;
        $this->rangeMax = $range_max;
        return $this;
    }

    /**
     * @param $admin
     * @return $this
     * @author 陈妙威
     */
    public function withIsMerchant($admin)
    {
        $this->isMerchant = $admin;
        return $this;
    }

    /**
     * @param $admin
     * @return $this
     * @author 陈妙威
     */
    public function withIsManager($admin)
    {
        $this->isManager = $admin;
        return $this;
    }

    /**
     * @param $system_agent
     * @return $this
     * @author 陈妙威
     */
    public function withIsSystemAgent($system_agent)
    {
        $this->isSystemAgent = $system_agent;
        return $this;
    }

    /**
     * @param $mailing_list
     * @return $this
     * @author 陈妙威
     */
    public function withIsMailingList($mailing_list)
    {
        $this->isMailingList = $mailing_list;
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function withIsDisabled($disabled)
    {
        $this->isDisabled = $disabled;
        return $this;
    }

    /**
     * @param $approved
     * @return $this
     * @author 陈妙威
     */
    public function withIsApproved($approved)
    {
        $this->isApproved = $approved;
        return $this;
    }

    /**
     * @param $like
     * @return $this
     * @author 陈妙威
     */
    public function withNameLike($like)
    {
        $this->nameLike = $like;
        return $this;
    }

    /**
     * @param array $tokens
     * @return $this
     * @author 陈妙威
     */
    public function withNameTokens(array $tokens)
    {
        $this->nameTokens = array_values($tokens);
        return $this;
    }

    /**
     * @param array $prefixes
     * @return $this
     * @author 陈妙威
     */
    public function withNamePrefixes(array $prefixes)
    {
        $this->namePrefixes = $prefixes;
        return $this;
    }

    /**
     * @param $enrolled
     * @return $this
     * @author 陈妙威
     */
    public function withIsEnrolledInMultiFactor($enrolled)
    {
        $this->isEnrolledInMultiFactor = $enrolled;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needPrimaryEmail($need)
    {
        $this->needPrimaryEmail = $need;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needProfile($need)
    {
        $this->needProfile = $need;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needProfileImage($need)
    {
        $cache_key = PhabricatorUserProfileImageCacheType::KEY_URI;

        if ($need) {
            $this->cacheKeys[$cache_key] = true;
        } else {
            unset($this->cacheKeys[$cache_key]);
        }

        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needAvailability($need)
    {
        $this->needAvailability = $need;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needUserSettings($need)
    {
        $cache_key = PhabricatorUserPreferencesCacheType::KEY_PREFERENCES;

        if ($need) {
            $this->cacheKeys[$cache_key] = true;
        } else {
            unset($this->cacheKeys[$cache_key]);
        }

        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needBadgeAwards($need)
    {
        $cache_key = PhabricatorUserBadgesCacheType::KEY_BADGES;

        if ($need) {
            $this->cacheKeys[$cache_key] = true;
        } else {
            unset($this->cacheKeys[$cache_key]);
        }

        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorUser();
    }

    /**
     * @return null
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $data = $this->loadStandardPage();
        return $data;
    }

    /**
     * @param PhabricatorUser[] $users
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    protected function didFilterPage(array $users)
    {
        if ($this->needProfile) {
            /** @var PhabricatorUser[] $user_list */
            $user_list = mpull($users, null, 'getPHID');
            $profiles = UserProfiles::find()->where(['IN', 'user_phid', array_keys($user_list)])->all();
            $profiles = mpull($profiles, null, 'getUserPHID');
            foreach ($user_list as $user_phid => $user) {
                $profile = ArrayHelper::getValue($profiles, $user_phid);
                if (!$profile) {
                    $profile = UserProfiles::initializeNewProfile($user);
                }
                $user->attachUserProfile($profile);
            }
        }

        if ($this->needAvailability) {
            $rebuild = array();
            foreach ($users as $user) {
                $cache = $user->getAvailabilityCache();
                if ($cache !== null) {
                    $user->attachAvailability($cache);
                } else {
                    $rebuild[] = $user;
                }
            }

            if ($rebuild) {
                $this->rebuildAvailabilityCache($rebuild);
            }
        }

        $this->fillUserCaches($users);

        return $users;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldGroupQueryResultRows()
    {
        if ($this->nameTokens) {
            return true;
        }
        return parent::shouldGroupQueryResultRows();
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    protected function buildJoinClause()
    {
        $joins = parent::buildJoinClause();
        if ($this->emails) {
            $this->innerJoin(PhabricatorUserEmail::tableName() . " email", 'email.user_phid = user.phid');
        }

        if ($this->nameTokens) {
            foreach ($this->nameTokens as $key => $token) {
                $token_table = 'token_' . $key;
                $userTableName = PhabricatorUser::tableName();
                $this->innerJoin(UserNametoken::tableName() . " {$token_table}", "{$token_table}.user_id = {$userTableName}.id AND {$token_table}.token LIKE :token", [
                    ":token" => $token
                ]);
            }
        }

        return $joins;
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $where = parent::buildWhereClause();
        if ($this->usernames !== null) {
            $this->andWhere(['IN', 'username', $this->usernames]);
        }

        if ($this->namePrefixes) {
            $parts = array();
            foreach ($this->namePrefixes as $name_prefix) {
                $parts[] = ['LIKE', 'username', "{$name_prefix}%", false];
            }
            if (count($parts) === 1) {
                $head = head($parts);
                $this->andWhere($head);
            } else {
                $this->andWhere(['AND', $parts]);
            }
        }

        if ($this->emails !== null) {
            $this->andWhere(['IN', 'email.address', $this->emails]);
        }

        if ($this->realnames !== null) {
            $this->andWhere(['IN', 'real_name', $this->realnames]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->dateCreatedAfter) {
            $this->andWhere(['>=', 'created_at', $this->dateCreatedAfter]);
        }

        if ($this->dateCreatedBefore) {
            $this->andWhere(['<=', 'created_at', $this->dateCreatedBefore]);
        }

        if ($this->isAdmin !== null) {
            $this->andWhere(['is_admin' => (int)$this->isAdmin]);
        }

        if ($this->isMerchant !== null) {
            $this->andWhere(['is_merchant' => (int)$this->isMerchant]);
        }

        if ($this->isManager !== null) {
            $this->andWhere(['is_manager' => (int)$this->isManager]);
        }


        if ($this->isDisabled !== null) {
            $this->andWhere(['is_disabled' => (int)$this->isDisabled]);
        }

        if ($this->isApproved !== null) {
            $this->andWhere(['is_approved' => (int)$this->isApproved]);
        }

        if ($this->isSystemAgent !== null) {
            $this->andWhere(['is_system_agent' => (int)$this->isSystemAgent]);
        }

        if ($this->isMailingList !== null) {
            $this->andWhere(['is_mailing_list' => (int)$this->isMailingList]);
        }

        if ($this->rangeMin !== null) {
            $this->andWhere(['>=', 'created_at', $this->rangeMin]);
        }

        if ($this->rangeMax !== null) {
            $this->andWhere(['<=', 'created_at', $this->rangeMax]);
        }

        if (strlen($this->nameLike)) {
            $where[] = [
                'OR',
                ['LIKE', 'username', "{$this->nameLike}%", false],
                ['LIKE', 'real_anme', "{$this->nameLike}%", false],
            ];
        }

        if ($this->isEnrolledInMultiFactor !== null) {
            $this->andWhere(['is_enrolled_in_multi_factor' => (int)$this->isEnrolledInMultiFactor]);
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'user';
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function getOrderableColumns()
    {
        return parent::getOrderableColumns() + array(
                'username' => array(
                    'table' => 'user',
                    'column' => 'username',
                    'type' => 'string',
                    'reverse' => true,
                    'unique' => true,
                ),
            );
    }

    /**
     * @param $cursor
     * @param array $keys
     * @return array
     * @author 陈妙威
     */
    protected function getPagingValueMap($cursor, array $keys)
    {
        $user = $this->loadCursorObject($cursor);
        return array(
            'id' => $user->getID(),
            'username' => $user->getUsername(),
        );
    }

    /**
     * @param array $rebuild
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    private function rebuildAvailabilityCache(array $rebuild)
    {
        $rebuild = mpull($rebuild, null, 'getPHID');

        // Limit the window we look at because far-future events are largely
        // irrelevant and this makes the cache cheaper to build and allows it to
        // self-heal over time.
        $min_range = PhabricatorTime::getNow();
        $max_range = $min_range + phutil_units('72 hours in seconds');

        // NOTE: We don't need to generate ghosts here, because we only care if
        // the user is attending, and you can't attend a ghost event: RSVP'ing
        // to it creates a real event.

//        $events = (new PhabricatorCalendarEventQuery())
//            ->setViewer(PhabricatorUser::getOmnipotentUser())
//            ->withInvitedPHIDs(array_keys($rebuild))
//            ->withIsCancelled(false)
//            ->withDateRange($min_range, $max_range)
//            ->execute();

        // Group all the events by invited user. Only examine events that users
        // are actually attending.
        $map = array();
        $invitee_map = array();
//        foreach ($events as $event) {
//            foreach ($event->getInvitees() as $invitee) {
//                if (!$invitee->isAttending()) {
//                    continue;
//                }
//
//                // If the user is set to "Available" for this event, don't consider it
//                // when computing their away status.
//                if (!$invitee->getDisplayAvailability($event)) {
//                    continue;
//                }
//
//                $invitee_phid = $invitee->getInviteePHID();
//                if (!isset($rebuild[$invitee_phid])) {
//                    continue;
//                }
//
//                $map[$invitee_phid][] = $event;
//
//                $event_phid = $event->getPHID();
//                $invitee_map[$invitee_phid][$event_phid] = $invitee;
//            }
//        }

        // We need to load these users' timezone settings to figure out their
        // availability if they're attending all-day events.
        $this->needUserSettings(true);
        $this->fillUserCaches($rebuild);

        foreach ($rebuild as $phid => $user) {
            $events = ArrayHelper::getValue($map, $phid, array());

            // We loaded events with the omnipotent user, but want to shift them
            // into the user's timezone before building the cache because they will
            // be unavailable during their own local day.
            foreach ($events as $event) {
                $event->applyViewerTimezone($user);
            }

            $cursor = $min_range;
            $next_event = null;
            if ($events) {
                // Find the next time when the user has no meetings. If we move forward
                // because of an event, we check again for events after that one ends.
                while (true) {
                    foreach ($events as $event) {
                        $from = $event->getStartDateTimeEpochForCache();
                        $to = $event->getEndDateTimeEpochForCache();
                        if (($from <= $cursor) && ($to > $cursor)) {
                            $cursor = $to;
                            if (!$next_event) {
                                $next_event = $event;
                            }
                            continue 2;
                        }
                    }
                    break;
                }
            }

            if ($cursor > $min_range) {
                $invitee = $invitee_map[$phid][$next_event->getPHID()];
                $availability_type = $invitee->getDisplayAvailability($next_event);
                $availability = array(
                    'until' => $cursor,
                    'eventPHID' => $next_event->getPHID(),
                    'availability' => $availability_type,
                );

                // We only cache this availability until the end of the current event,
                // since the event PHID (and possibly the availability type) are only
                // valid for that long.

                // NOTE: This doesn't handle overlapping events with the greatest
                // possible care. In theory, if you're attending multiple events
                // simultaneously we should accommodate that. However, it's complex
                // to compute, rare, and probably not confusing most of the time.

                $availability_ttl = $next_event->getEndDateTimeEpochForCache();
            } else {
                $availability = array(
                    'until' => null,
                    'eventPHID' => null,
                    'availability' => null,
                );

                // Cache that the user is available until the next event they are
                // invited to starts.
                $availability_ttl = $max_range;
                foreach ($events as $event) {
                    $from = $event->getStartDateTimeEpochForCache();
                    if ($from > $cursor) {
                        $availability_ttl = min($from, $availability_ttl);
                    }
                }
            }

            // Never TTL the cache to longer than the maximum range we examined.
            $availability_ttl = min($availability_ttl, $max_range);

            $user->writeAvailabilityCache($availability, $availability_ttl);
            $user->attachAvailability($availability);
        }
    }

    /**
     * @param array $users
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    private function fillUserCaches(array $users)
    {
        if (!$this->cacheKeys) {
            return;
        }

        $user_map = mpull($users, null, 'getPHID');
        $keys = array_keys($this->cacheKeys);

        $hashes = array();
        foreach ($keys as $key) {
            $hashes[] = PhabricatorHash::digestForIndex($key);
        }

        $types = PhabricatorUserCacheType::getAllCacheTypes();

        // First, pull any available caches. If we wanted to be particularly clever
        // we could do this with JOINs in the main query.

//        $cache_table = new UserCache();
//        $cache_conn = $cache_table->establishConnection('r');
        $cache_data = PhabricatorUserCache::find()
            ->select(['cache_key', 'user_phid', 'cache_data', 'cache_type'])
            ->andWhere(['IN', 'cache_index', $hashes])
            ->andWhere(['IN', 'user_phid', array_keys($user_map)])
            ->all();
//        $cache_data = queryfx_all(
//            $cache_conn,
//            'SELECT cacheKey, userPHID, cache_data, cacheType FROM %T
//        WHERE cacheIndex IN (%Ls) AND userPHID IN (%Ls)',
//            $cache_table->getTableName(),
//            $hashes,
//            array_keys($user_map));

        $skip_validation = array();

        // After we read caches from the database, discard any which have data that
        // invalid or out of date. This allows cache types to implement TTLs or
        // versions instead of or in addition to explicit cache clears.
        foreach ($cache_data as $row_key => $row) {
            $cache_type = $row['cache_type'];

            if (isset($skip_validation[$cache_type])) {
                continue;
            }

            if (empty($types[$cache_type])) {
                unset($cache_data[$row_key]);
                continue;
            }

            $type = $types[$cache_type];
            if (!$type->shouldValidateRawCacheData()) {
                $skip_validation[$cache_type] = true;
                continue;
            }

            $user = $user_map[$row['user_phid']];
            $raw_data = $row['cache_data'];
            if (!$type->isRawCacheDataValid($user, $row['cache_key'], $raw_data)) {
                unset($cache_data[$row_key]);
                continue;
            }
        }

        $need = array();

        $cache_data = igroup($cache_data, 'user_phid');
        foreach ($user_map as $user_phid => $user) {
            $raw_rows = ArrayHelper::getValue($cache_data, $user_phid, array());
            $raw_data = ipull($raw_rows, 'cache_data', 'cache_key');

            foreach ($keys as $key) {
                if (isset($raw_data[$key]) || array_key_exists($key, $raw_data)) {
                    continue;
                }
                $need[$key][$user_phid] = $user;
            }

            $user->attachRawCacheData($raw_data);
        }

        // If we missed any cache values, bulk-construct them now. This is
        // usually much cheaper than generating them on-demand for each user
        // record.

        if (!$need) {
            return;
        }

        $writes = array();
        foreach ($need as $cache_key => $need_users) {
            $type = PhabricatorUserCacheType::getCacheTypeForKey($cache_key);
            if (!$type) {
                continue;
            }

            $data = $type->newValueForUsers($cache_key, $need_users);

            foreach ($data as $user_phid => $raw_value) {
                $data[$user_phid] = $raw_value;
                $writes[] = array(
                    'userPHID' => $user_phid,
                    'key' => $cache_key,
                    'type' => $type,
                    'value' => $raw_value,
                );
            }

            foreach ($need_users as $user_phid => $user) {
                if (isset($data[$user_phid]) || array_key_exists($user_phid, $data)) {
                    $user->attachRawCacheData(
                        array(
                            $cache_key => $data[$user_phid],
                        ));
                }
            }
        }

        PhabricatorUserCache::writeCaches($writes);
    }

    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }
}
