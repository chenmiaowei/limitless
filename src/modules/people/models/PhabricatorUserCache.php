<?php

namespace orangins\modules\people\models;

use AphrontWriteGuard;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\people\cache\PhabricatorUserCacheType;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_cache".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $cache_index
 * @property string $cache_key
 * @property string $cache_data
 * @property string $cache_type
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorUserCache extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_cache';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'cache_index', 'cache_key', 'cache_data', 'cache_type'], 'required'],
            [['cache_data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_phid'], 'string', 'max' => 64],
            [['cache_index'], 'string', 'max' => 12],
            [['cache_key'], 'string', 'max' => 255],
            [['cache_type'], 'string', 'max' => 32],
            [['user_phid', 'cache_index'], 'unique', 'targetAttribute' => ['user_phid', 'cache_index']],
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
            'cache_index' => Yii::t('app', 'Cache Index'),
            'cache_key' => Yii::t('app', 'Cache Key'),
            'cache_data' => Yii::t('app', 'Cache Data'),
            'cache_type' => Yii::t('app', 'Cache Type'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorUserCacheType $type
     * @param $key
     * @param array $user_phids
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function readCaches(PhabricatorUserCacheType $type, $key, array $user_phids)
    {
        $rows = PhabricatorUserCache::find()
            ->select(['user_phid', 'cache_data'])
            ->andWhere(['IN', 'user_phid', $user_phids])
            ->andWhere(['cache_type' => $type->getUserCacheType()])
            ->andWhere(['cache_index' => PhabricatorHash::digestForIndex($key)])
            ->all();
        return ipull($rows, 'cache_data', 'user_phid');
    }


    /**
     * @param PhabricatorUserCacheType $type
     * @param $key
     * @param $user_phid
     * @param $raw_value
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public static function writeCache(
        PhabricatorUserCacheType $type,
        $key,
        $user_phid,
        $raw_value)
    {
        self::writeCaches(
            array(
                array(
                    'type' => $type,
                    'key' => $key,
                    'userPHID' => $user_phid,
                    'value' => $raw_value,
                ),
            ));
    }


    /**
     * @param array $values
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public static function writeCaches(array $values)
    {
        if (PhabricatorEnv::isReadOnly()) {
            return;
        }

        if (!$values) {
            return;
        }
        foreach ($values as $value) {
            $key = $value['key'];
            /** @var PhabricatorUserCacheType $type */
            $type = $value['type'];
            $arr = [
                'user_phid' => $value['userPHID'],
                'cache_index' => PhabricatorHash::digestForIndex($key),
                'cache_key' => $key,
                'cache_data' => $value['value'],
                'cache_type' => $type->getUserCacheType(),
            ];
            \Yii::$app->getDb()->createCommand()->upsert(self::tableName(), $arr, [
                'user_phid' => new \yii\db\Expression('VALUES(user_phid)'),
                'cache_index' => new \yii\db\Expression('VALUES(cache_index)'),
                'cache_key' => new \yii\db\Expression('VALUES(cache_key)'),
                'cache_data' => new \yii\db\Expression('VALUES(cache_data)'),
                'cache_type' => new \yii\db\Expression('VALUES(cache_type)'),
            ])->execute();
        }
    }

    /**
     * @param $key
     * @param $user_phid
     * @return void
     * @throws \Exception
     * @author 陈妙威
     */
    public static function clearCache($key, $user_phid)
    {
        return self::clearCaches($key, array($user_phid));
    }

    /**
     * @param $key
     * @param array $user_phids
     * @throws \Exception
     * @author 陈妙威
     */
    public static function clearCaches($key, array $user_phids)
    {
        if (PhabricatorEnv::isReadOnly()) {
            return;
        }

        if (!$user_phids) {
            return;
        }
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        self::deleteAll([
            'AND',
            [
                'cache_index' => PhabricatorHash::digestForIndex($key),
            ],
            ['IN', 'user_phid', $user_phids]
        ]);
        unset($unguarded);
    }

    /**
     * @param $key
     * @throws \Exception
     * @author 陈妙威
     */
    public static function clearCacheForAllUsers($key)
    {
        if (PhabricatorEnv::isReadOnly()) {
            return;
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        self::deleteAll([
            'cache_index' => PhabricatorHash::digestForIndex($key),
        ]);
        unset($unguarded);
    }
}
