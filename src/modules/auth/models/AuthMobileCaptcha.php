<?php

namespace orangins\modules\auth\models;

use Yii;

/**
 * This is the model class for table "auth_mobile_captcha".
 *
 * @property int $id
 * @property string $mobile
 * @property string $captcha
 * @property int $is_expired
 * @property int $expired_at
 * @property string $ip
 * @property int $created_at
 * @property int $updated_at
 */
class AuthMobileCaptcha extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_mobile_captcha';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mobile', 'captcha', 'expired_at'], 'required'],
            [['is_expired', 'expired_at', 'created_at', 'updated_at'], 'integer'],
            [['mobile'], 'string', 'max' => 16],
            [['captcha'], 'string', 'max' => 8],
            [['ip'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'mobile' => Yii::t('app', 'Mobile'),
            'captcha' => Yii::t('app', 'Captcha'),
            'is_expired' => Yii::t('app', 'Is Expired'),
            'expired_at' => Yii::t('app', 'Expired At'),
            'ip' => Yii::t('app', 'Ip'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
