<?php

namespace orangins\modules\auth\models;

use Yii;

/**
 * This is the model class for table "auth_hmackey".
 *
 * @property int $id
 * @property string $key_name
 * @property string $key_value
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthHMACKey extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_hmackey';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['key_name', 'key_value'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['key_name'], 'string', 'max' => 64],
            [['key_value'], 'string', 'max' => 128],
            [['key_name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'key_name' => Yii::t('app', 'Key Name'),
            'key_value' => Yii::t('app', 'Key Value'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getKeyName()
    {
        return $this->key_name;
    }

    /**
     * @param string $key_name
     * @return self
     */
    public function setKeyName($key_name)
    {
        $this->key_name = $key_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyValue()
    {
        return $this->key_value;
    }

    /**
     * @param string $key_value
     * @return self
     */
    public function setKeyValue($key_value)
    {
        $this->key_value = $key_value;
        return $this;
    }
}
