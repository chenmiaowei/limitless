<?php

namespace orangins\modules\notification\model;

use AphrontWriteGuard;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\notification\query\PhabricatorNotificationQuery;
use orangins\modules\people\cache\PhabricatorUserNotificationCountCacheType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use Yii;

/**
 * This is the model class for table "feed_storynotification".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $primary_object_phid
 * @property int $chronological_key
 * @property int $has_viewed
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFeedStoryNotification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feed_storynotification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'primary_object_phid', 'chronological_key', 'has_viewed'], 'required'],
            [['chronological_key', 'has_viewed'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_phid', 'primary_object_phid'], 'string', 'max' => 64],
            [['user_phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'primary_object_phid' => Yii::t('app', 'Primary Object Phid'),
            'chronological_key' => Yii::t('app', 'Chronological Key'),
            'has_viewed' => Yii::t('app', 'Has Viewed'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorNotificationQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorNotificationQuery(get_called_class());
    }

    /**
     * @param PhabricatorUser $user
     * @param $object_phid
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public static function updateObjectNotificationViews(
        PhabricatorUser $user,
        $object_phid)
    {

        if (PhabricatorEnv::isReadOnly()) {
            return;
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        PhabricatorFeedStoryNotification::updateAll([
            'has_viewed' => 1
        ], [
            'user_phid' => $user->getPHID(),
            'primary_object_phid' => $object_phid,
            'has_viewed' => 0,
        ]);
        unset($unguarded);
        $count_key = PhabricatorUserNotificationCountCacheType::KEY_COUNT;
        PhabricatorUserCache::clearCache($count_key, $user->getPHID());
        $user->clearCacheData($count_key);
    }
}
