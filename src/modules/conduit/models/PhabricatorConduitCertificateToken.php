<?php

namespace orangins\modules\conduit\models;

use Yii;

/**
 * This is the model class for table "conduit_certificatetoken".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $token
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorConduitCertificateToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conduit_certificatetoken';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'token'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['user_phid', 'token'], 'string', 'max' => 64],
            [['user_phid'], 'unique'],
            [['token'], 'unique'],
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
            'token' => Yii::t('app', 'Token'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
