<?php

namespace orangins\modules\oauthserver\models;

use Yii;

/**
 * This is the model class for table "oauth_server_oauthserveraccesstoken".
 *
 * @property int $id
 * @property string $token
 * @property string $user_phid
 * @property string $client_phid
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorOAuthServerAccessToken extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_server_oauthserveraccesstoken';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token', 'user_phid', 'client_phid'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['token'], 'string', 'max' => 32],
            [['user_phid', 'client_phid'], 'string', 'max' => 64],
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
            'token' => Yii::t('app', 'Token'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'client_phid' => Yii::t('app', 'Client PHID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientPHID()
    {
        return $this->client_phid;
    }

    /**
     * @param string $client_phid
     * @return self
     */
    public function setClientPHID($client_phid)
    {
        $this->client_phid = $client_phid;
        return $this;
    }
}
