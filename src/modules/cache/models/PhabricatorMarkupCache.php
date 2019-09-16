<?php

namespace orangins\modules\cache\models;

use Yii;

/**
 * This is the model class for table "cache_markupcache".
 *
 * @property int $id
 * @property string $cache_key
 * @property string $cache_data
 * @property string $metadata
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorMarkupCache extends PhabricatorCacheDAO
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cache_markupcache';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cache_key', 'cache_data', 'metadata'], 'required'],
            [['cache_data', 'metadata'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['cache_key'], 'string', 'max' => 128],
            [['cache_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'cache_key' => Yii::t('app', 'Cache Key'),
            'cache_data' => Yii::t('app', 'Cache Data'),
            'metadata' => Yii::t('app', 'Metadata'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cache_key;
    }

    /**
     * @param string $cache_key
     * @return self
     */
    public function setCacheKey($cache_key)
    {
        $this->cache_key = $cache_key;
        return $this;
    }


    /**
     * @return string
     */
    public function getCacheData()
    {
        return unserialize($this->cache_data);
    }

    /**
     * @param string $cache_data
     * @return self
     * @throws \Exception
     */
    public function setCacheData($cache_data)
    {
        $this->cache_data = serialize($cache_data);
        return $this;
    }

    /**
     * @return string
     */
    public function getMetadata()
    {
        return phutil_json_decode($this->metadata);
    }

    /**
     * @param string $metadata
     * @return self
     * @throws \Exception
     */
    public function setMetadata($metadata)
    {
        $this->metadata = phutil_json_encode($metadata);
        return $this;
    }
}
