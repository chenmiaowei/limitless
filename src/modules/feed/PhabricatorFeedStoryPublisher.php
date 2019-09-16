<?php

namespace orangins\modules\feed;

use Exception;
use Filesystem;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\OranginsObject;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\feed\models\PhabricatorFeedStoryReference;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\metamta\query\PhabricatorMetaMTAMemberQuery;
use orangins\modules\notification\client\PhabricatorNotificationClient;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\people\cache\PhabricatorUserNotificationCountCacheType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorEmailTagsSetting;

/**
 * Class PhabricatorFeedStoryPublisher
 * @package orangins\modules\feed
 * @author 陈妙威
 */
final class PhabricatorFeedStoryPublisher extends OranginsObject
{

    /**
     * @var
     */
    private $relatedPHIDs;
    /**
     * @var
     */
    private $storyType;
    /**
     * @var
     */
    private $storyData;
    /**
     * @var
     */
    private $storyTime;
    /**
     * @var
     */
    private $storyAuthorPHID;
    /**
     * @var
     */
    private $primaryObjectPHID;
    /**
     * @var array
     */
    private $subscribedPHIDs = array();
    /**
     * @var array
     */
    private $mailRecipientPHIDs = array();
    /**
     * @var
     */
    private $notifyAuthor;
    /**
     * @var array
     */
    private $mailTags = array();
    /**
     * @var array
     */
    private $unexpandablePHIDs = array();

    /**
     * @param array $mail_tags
     * @return $this
     * @author 陈妙威
     */
    public function setMailTags(array $mail_tags)
    {
        $this->mailTags = $mail_tags;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMailTags()
    {
        return $this->mailTags;
    }

    /**
     * @param $notify_author
     * @return $this
     * @author 陈妙威
     */
    public function setNotifyAuthor($notify_author)
    {
        $this->notifyAuthor = $notify_author;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNotifyAuthor()
    {
        return $this->notifyAuthor;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setRelatedPHIDs(array $phids)
    {
        $this->relatedPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setSubscribedPHIDs(array $phids)
    {
        $this->subscribedPHIDs = $phids;
        return $this;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPrimaryObjectPHID($phid)
    {
        $this->primaryObjectPHID = $phid;
        return $this;
    }

    /**
     * @param array $unexpandable_phids
     * @return $this
     * @author 陈妙威
     */
    public function setUnexpandablePHIDs(array $unexpandable_phids)
    {
        $this->unexpandablePHIDs = $unexpandable_phids;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getUnexpandablePHIDs()
    {
        return $this->unexpandablePHIDs;
    }

    /**
     * @param $story_type
     * @return $this
     * @author 陈妙威
     */
    public function setStoryType($story_type)
    {
        $this->storyType = $story_type;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @author 陈妙威
     */
    public function setStoryData(array $data)
    {
        $this->storyData = $data;
        return $this;
    }

    /**
     * @param $time
     * @return $this
     * @author 陈妙威
     */
    public function setStoryTime($time)
    {
        $this->storyTime = $time;
        return $this;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setStoryAuthorPHID($phid)
    {
        $this->storyAuthorPHID = $phid;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setMailRecipientPHIDs(array $phids)
    {
        $this->mailRecipientPHIDs = $phids;
        return $this;
    }

    /**
     * @return PhabricatorFeedStoryData
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function publish()
    {
        $class = $this->storyType;
        if (!$class) {
            throw new Exception(
                pht(
                    'Call %s before publishing!',
                    'setStoryType()'));
        }

        $phabricatorFeedStories = PhabricatorFeedStory::getAllTypes();


        if (!isset($phabricatorFeedStories[$class])) {
            throw new Exception(
                pht(
                    "Story type must be a valid class name and must subclass %s. " .
                    "'%s' is not a loadable class.",
                    'PhabricatorFeedStory',
                    $class));
        }

        $chrono_key = $this->generateChronologicalKey();
        $story = new PhabricatorFeedStoryData();
        $story->setStoryType($this->storyType);
        $story->setStoryData($this->storyData);
        $story->setAuthorPHID((string)$this->storyAuthorPHID);
        $story->setChronologicalKey($chrono_key);
        $story->save();

        if ($this->relatedPHIDs) {
            $ref = new PhabricatorFeedStoryReference();

            $rows = [];
            foreach (array_unique($this->relatedPHIDs) as $phid) {
                $rows[] = [
                    'object_phid' => $phid,
                    'chronological_key' => $chrono_key,
                ];
            }
            $ref->getDb()->createCommand()->batchInsert($ref::tableName(), [
                'object_phid',
                'chronological_key',
            ], $rows)->execute();
        }

        $subscribed_phids = $this->subscribedPHIDs;
        if ($subscribed_phids) {
            $subscribed_phids = $this->filterSubscribedPHIDs($subscribed_phids);
            $this->insertNotifications($chrono_key, $subscribed_phids);
            $this->sendNotification($chrono_key, $subscribed_phids);
        }

        PhabricatorWorker::scheduleTask(
            'FeedPublisherWorker',
            array(
                'key' => $chrono_key,
            ));

        return $story;
    }

    /**
     * @param $chrono_key
     * @param array $subscribed_phids
     * @author 陈妙威
     * @throws Exception
     */
    private function insertNotifications($chrono_key, array $subscribed_phids)
    {
        if (!$this->primaryObjectPHID) {
            throw new Exception(
                pht(
                    'You must call %s if you %s!',
                    'setPrimaryObjectPHID()',
                    'setSubscribedPHIDs()'));
        }

        $notif = new PhabricatorFeedStoryNotification();
        $sql = array();

        $will_receive_mail = array_fill_keys($this->mailRecipientPHIDs, true);

        $user_phids = array_unique($subscribed_phids);
        foreach ($user_phids as $user_phid) {
            if (isset($will_receive_mail[$user_phid])) {
                $mark_read = 1;
            } else {
                $mark_read = 0;
            }

            $sql[] = [
                'primary_object_phid' => $this->primaryObjectPHID,
                'user_phid' => $user_phid,
                'chronological_key' => $chrono_key,
                'has_viewed' => $mark_read,
            ];
        }

        if ($sql) {
            $notif->getDb()->createCommand()->batchInsert($notif::tableName(), [
                'primary_object_phid',
                'user_phid',
                'chronological_key',
                'has_viewed',
            ], $sql)->execute();
        }

        PhabricatorUserCache::clearCaches(
            PhabricatorUserNotificationCountCacheType::KEY_COUNT,
            $user_phids);
    }

    /**
     * @param $chrono_key
     * @param array $subscribed_phids
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function sendNotification($chrono_key, array $subscribed_phids)
    {
        $data = array(
            'key' => (string)$chrono_key,
            'type' => 'notification',
            'subscribers' => $subscribed_phids,
        );

        PhabricatorNotificationClient::tryToPostMessage($data);
    }

    /**
     * Remove PHIDs who should not receive notifications from a subscriber list.
     *
     * @param array $phids
     * @return array<phid> List of actual subscribers.
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    private function filterSubscribedPHIDs(array $phids)
    {
        $phids = $this->expandRecipients($phids);

        $tags = $this->getMailTags();
        if ($tags) {
            $all_prefs = PhabricatorUserPreferences::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withUserPHIDs($phids)
                ->needSyntheticPreferences(true)
                ->execute();
            $all_prefs = mpull($all_prefs, null, 'getUserPHID');
        }

        $pref_default = PhabricatorEmailTagsSetting::VALUE_EMAIL;
        $pref_ignore = PhabricatorEmailTagsSetting::VALUE_IGNORE;

        $keep = array();
        foreach ($phids as $phid) {
            if (($phid == $this->storyAuthorPHID) && !$this->getNotifyAuthor()) {
                continue;
            }

            if ($tags && isset($all_prefs[$phid])) {
                $mailtags = $all_prefs[$phid]->getSettingValue(
                    PhabricatorEmailTagsSetting::SETTINGKEY);

                $notify = false;
                foreach ($tags as $tag) {
                    // If this is set to "email" or "notify", notify the user.
                    if ((int)idx($mailtags, $tag, $pref_default) != $pref_ignore) {
                        $notify = true;
                        break;
                    }
                }

                if (!$notify) {
                    continue;
                }
            }

            $keep[] = $phid;
        }

        return array_values(array_unique($keep));
    }

    /**
     * @param array $phids
     * @return array
     * @author 陈妙威
     */
    private function expandRecipients(array $phids)
    {
        $expanded_phids = (new PhabricatorMetaMTAMemberQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs($phids)
            ->executeExpansion();

        // Filter out unexpandable PHIDs from the results. The typical case for
        // this is that resigned reviewers should not be notified just because
        // they are a member of some project or package reviewer.

        $original_map = array_fuse($phids);
        $unexpandable_map = array_fuse($this->unexpandablePHIDs);

        foreach ($expanded_phids as $key => $phid) {
            // We can keep this expanded PHID if it was present originally.
            if (isset($original_map[$phid])) {
                continue;
            }

            // We can also keep it if it isn't marked as unexpandable.
            if (!isset($unexpandable_map[$phid])) {
                continue;
            }

            // If it's unexpandable and we produced it by expanding recipients,
            // throw it away.
            unset($expanded_phids[$key]);
        }
        $expanded_phids = array_values($expanded_phids);

        return $expanded_phids;
    }

    /**
     * We generate a unique chronological key for each story type because we want
     * to be able to page through the stream with a cursor (i.e., select stories
     * after ID = X) so we can efficiently perform filtering after selecting data,
     * and multiple stories with the same ID make this cumbersome without putting
     * a bunch of logic in the client. We could use the primary key, but that
     * would prevent publishing stories which happened in the past. Since it's
     * potentially useful to do that (e.g., if you're importing another data
     * source) build a unique key for each story which has chronological ordering.
     *
     * @return string A unique, time-ordered key which identifies the story.
     * @throws \AphrontCountQueryException
     * @throws \FilesystemException
     * @throws \yii\db\Exception
     */
    private function generateChronologicalKey()
    {
        // Use the epoch timestamp for the upper 32 bits of the key. Default to
        // the current time if the story doesn't have an explicit timestamp.
        $time = nonempty($this->storyTime, time());

        // Generate a random number for the lower 32 bits of the key.
        $rand = head(unpack('L', Filesystem::readRandomBytes(4)));

        // On 32-bit machines, we have to get creative.
        if (PHP_INT_SIZE < 8) {
            // We're on a 32-bit machine.
            if (function_exists('bcadd')) {
                // Try to use the 'bc' extension.
                return bcadd(bcmul($time, bcpow(2, 32)), $rand);
            } else {
                // Do the math in MySQL. TODO: If we formalize a bc dependency, get
                // rid of this.
                $conn_r = (new PhabricatorFeedStoryData());
                $result = $conn_r->getDb()->createCommand("SELECT (:time << 32) + :rand as N FROM `{$conn_r::tableName()}`", [
                    ":time" => $time,
                    ":rand" => $rand,
                ])->queryOne();
                return $result['N'];
            }
        } else {
            // This is a 64 bit machine, so we can just do the math.
            return ($time << 32) + $rand;
        }
    }
}
