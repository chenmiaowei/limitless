<?php
/**
 * Created by PhpStorm.
 * User: qin
 * Date: 17/2/16
 * Time: 下午7:53
 */

namespace orangins\modules\auth\provider\wxamp;

use Exception;

/**
 * Class WxDataCrypt
 * @package orangins\modules\auth\provider\wxamp
 * @author 陈妙威
 */
class WxDataCrypt
{
    /**
     * @var string
     */
    private $appid;
    /**
     * @var string
     */
    private $sessionKey;
    /**
     * @var
     */
    private $key;

    /**
     * 构造函数
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid string 小程序的appid
     * @throws Exception
     */
    public function __construct($appid, $sessionKey)
    {
        $this->sessionKey = $sessionKey;
        $this->appid = $appid;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     * @throws Exception
     */
    public function decryptData($encryptedData, $iv, &$data)
    {
        if (strlen($this->sessionKey) != 24) {
            return 41001;
        }
        if (strlen($iv) != 24) {
            return 41002;
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $aesKey = base64_decode($this->sessionKey);
        $this->key = $aesKey;
        $result = $this->decrypt($aesCipher, $aesIV);

        if ($result[0] != 0) {
            return $result[0];
        }

        $dataObj = json_decode($result[1]);
        if ($dataObj == NULL) {
            return 41003;
        }

        if ($dataObj->watermark->appid != $this->appid) {
            return 41003;
        }
        $data = $result[1];
        return 0;
    }

    /**
     * 对密文进行解密
     * @param string $aesCipher 需要解密的密文
     * @param string $aesIV 解密的初始向量
     * @return array
     * @throws Exception
     */
    public function decrypt($aesCipher, $aesIV)
    {
        if(!function_exists("mcrypt_module_open")) {
            throw new Exception("\"mcrypt\" should be install at php.ini");
        }

        try {
            $module = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            @mcrypt_generic_init($module, $this->key, $aesIV);

            //解密
            $decrypted = @mdecrypt_generic($module, $aesCipher);
            @mcrypt_generic_deinit($module);
            @mcrypt_module_close($module);
        } catch (Exception $e) {
            \Yii::error($e);
            return array(41003, null);
        }

        try {
            //去除补位字符
            $result = $this->decode($decrypted);
        } catch (Exception $e) {
            \Yii::error($e);
            return array(41003, null);
        }
        return array(0, $result);
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string decrypted 解密后的明文
     * @return string 删除填充补位后的明文
     */
    function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}