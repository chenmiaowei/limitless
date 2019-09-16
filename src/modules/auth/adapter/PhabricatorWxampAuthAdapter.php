<?php

namespace orangins\modules\auth\adapter;

use orangins\modules\auth\provider\wxamp\WxDataCrypt;
use PhutilAuthAdapter;
use PhutilOpaqueEnvelope;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Authentication adapter for WordPress.com OAuth2.
 */
final class PhabricatorWxampAuthAdapter extends PhutilAuthAdapter
{
    /**
     * @var
     */
    public $oauthAccountData;

    /**
     * @var
     */
    private $clientID;
    /**
     * @var PhutilOpaqueEnvelope
     */
    private $clientSecret;

    /**
     * @var
     */
    private $encryptedData;

    /**
     * @var
     */
    private $code;

    /**
     * @var
     */
    private $iv;

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     * @return self
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * @param mixed $iv
     * @return self
     */
    public function setIv($iv)
    {
        $this->iv = $iv;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncryptedData()
    {
        return $this->encryptedData;
    }

    /**
     * @param mixed $encryptedData
     * @return self
     */
    public function setEncryptedData($encryptedData)
    {
        $this->encryptedData = $encryptedData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * @param mixed $clientID
     * @return self
     */
    public function setClientID($clientID)
    {
        $this->clientID = $clientID;
        return $this;
    }

    /**
     * @return PhutilOpaqueEnvelope
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param PhutilOpaqueEnvelope $clientSecret
     * @return self
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAdapterType()
    {
        return 'wxamp';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAdapterDomain()
    {
        return 'wechat.com';
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getAccountID()
    {
        return $this->getOAuthAccountData('openId');
    }


    /**
     * @return null|string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getAccountImageURI()
    {
        return $this->getOAuthAccountData('avatarUrl');
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getAccountRealName()
    {
        return $this->getOAuthAccountData('nickName');
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    protected function loadOAuthAccountData()
    {
        $appid = $this->getClientID();
        $secret = $this->getClientSecret()->openEnvelope();
        $code = $this->getCode();
        $requests_Response = \Requests::get('https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code');
        $sessionResponse = $requests_Response->body;
        $sessionResponse = json_decode($sessionResponse, true);

        if (ArrayHelper::getValue($sessionResponse, 'errcode')) {
            throw new \Exception(\Yii::t("app", 'The OAuth provider returned an error: {0} {1}',
                [
                    ArrayHelper::getValue($sessionResponse, 'errcode'),
                    ArrayHelper::getValue($sessionResponse, 'errmsg')
                ]));
        } else {
            $sessionKey = $sessionResponse['session_key'];
            $decrypt = new WxDataCrypt($appid, $sessionKey);
            $data = null;
            $errCode = $decrypt->decryptData($this->getEncryptedData(), $this->getIv(), $data);
            if ($errCode == 0) {
                $user = Json::decode($data);
                return $user;
            } else {
                throw new \Exception(\Yii::t("app", 'EncryptedData error: {0}',
                    [
                        $errCode,
                    ]));
            }
        }
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getOAuthAccountData($key, $default = null)
    {
        if ($this->oauthAccountData === null) {
            $this->oauthAccountData = $this->loadOAuthAccountData();
        }

        return idx($this->oauthAccountData, $key, $default);
    }
}
