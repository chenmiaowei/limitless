<?php

namespace orangins\modules\oauthserver\models;

use Yii;

/**
 * This is the model class for table "oauth_server_oauthserverauthorizationcode".
 *
 * @property int $id
 * @property string $code
 * @property string $client_phid
 * @property string $client_secret
 * @property string $user_phid
 * @property string $redirect_uri
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorOAuthServerAuthorizationCode extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_server_oauthserverauthorizationcode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'client_phid', 'client_secret', 'user_phid', 'redirect_uri'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['code', 'client_secret'], 'string', 'max' => 32],
            [['client_phid', 'user_phid'], 'string', 'max' => 64],
            [['redirect_uri'], 'string', 'max' => 255],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'code' => Yii::t('app', 'Code'),
            'client_phid' => Yii::t('app', 'Client PHID'),
            'client_secret' => Yii::t('app', 'Client Secret'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'redirect_uri' => Yii::t('app', 'Redirect URI'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return self
     */
    public function setCode($code)
    {
        $this->code = $code;
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

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param string $client_secret
     * @return self
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;
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
    public function getRedirectURI()
    {
        return $this->redirect_uri;
    }

    /**
     * @param string $redirect_uri
     * @return self
     */
    public function setRedirectURI($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
        return $this;
    }
}
