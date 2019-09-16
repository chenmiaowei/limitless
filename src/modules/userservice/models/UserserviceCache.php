<?php

namespace orangins\modules\userservice\models;

use Yii;

/**
 * This is the model class for table "userservice_cache".
 *
 * @property int $id
 * @property string $object_phid
 * @property double $amount
 * @property int $created_at
 * @property int $updated_at
 */
class UserserviceCache extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userservice_cache';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['amount'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
            [['object_phid'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return UserserviceCacheQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserserviceCacheQuery(get_called_class());
    }
}
