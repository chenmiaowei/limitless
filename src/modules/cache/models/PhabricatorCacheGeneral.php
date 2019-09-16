<?php

namespace orangins\modules\cache\models;

use Yii;

/**
 * This is the model class for table "cache_general".
 *
 * @property int $id
 * @property string $cache_key_hash
 * @property string $cache_key
 * @property string $cache_format
 * @property string $cache_data
 * @property int $cache_expires
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorCacheGeneral extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cache_general';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cache_key', 'cache_format', 'cache_data', 'cache_expires'], 'required'],
            [['cache_data'], 'string'],
            [['cache_expires'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['cache_key_hash'], 'string', 'max' => 12],
            [['cache_key'], 'string', 'max' => 128],
            [['cache_format'], 'string', 'max' => 16],
            [['cache_key_hash'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'cache_key_hash' => Yii::t('app', 'Cache Key Hash'),
            'cache_key' => Yii::t('app', 'Cache Key'),
            'cache_format' => Yii::t('app', 'Cache Format'),
            'cache_data' => Yii::t('app', 'Cache Data'),
            'cache_expires' => Yii::t('app', 'Cache Expires'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
