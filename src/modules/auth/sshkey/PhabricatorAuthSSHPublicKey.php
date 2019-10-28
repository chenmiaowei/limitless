<?php

namespace orangins\modules\auth\sshkey;

use CommandException;
use Filesystem;
use FilesystemException;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\OranginsObject;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\cache\PhabricatorCaches;
use PhutilProxyException;
use TempFile;
use Exception;
use Yii;

/**
 * Data structure representing a raw public key.
 */
final class PhabricatorAuthSSHPublicKey extends OranginsObject
{

    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $body;
    /**
     * @var
     */
    private $comment;


    /**
     * @param PhabricatorAuthSSHKey $key
     * @return PhabricatorAuthSSHPublicKey
     * @author 陈妙威
     */
    public static function newFromStoredKey(PhabricatorAuthSSHKey $key)
    {
        $public_key = new PhabricatorAuthSSHPublicKey();
        $public_key->type = $key->getKeyType();
        $public_key->body = $key->getKeyBody();
        $public_key->comment = $key->getKeyComment();

        return $public_key;
    }

    /**
     * @param $entire_key
     * @return PhabricatorAuthSSHPublicKey
     * @throws Exception
     * @author 陈妙威
     */
    public static function newFromRawKey($entire_key)
    {
        $entire_key = trim($entire_key);
        if (!strlen($entire_key)) {
            throw new Exception(Yii::t("app",'No public key was provided.'));
        }

        $parts = str_replace("\n", '', $entire_key);

        // The third field (the comment) can have spaces in it, so split this
        // into a maximum of three parts.
        $parts = preg_split('/\s+/', $parts, 3);

        if (preg_match('/private\s*key/i', $entire_key)) {
            // Try to give the user a better error message if it looks like
            // they uploaded a private key.
            throw new Exception(Yii::t("app",'Provide a public key, not a private key!'));
        }

        switch (count($parts)) {
            case 1:
                throw new Exception(
                    Yii::t("app",'Provided public key is not properly formatted.'));
            case 2:
                // Add an empty comment part.
                $parts[] = '';
                break;
            case 3:
                // This is the expected case.
                break;
        }

        list($type, $body, $comment) = $parts;

        $recognized_keys = array(
            'ssh-dsa',
            'ssh-dss',
            'ssh-rsa',
            'ssh-ed25519',
            'ecdsa-sha2-nistp256',
            'ecdsa-sha2-nistp384',
            'ecdsa-sha2-nistp521',
        );

        if (!in_array($type, $recognized_keys)) {
            $type_list = implode(', ', $recognized_keys);
            throw new Exception(
                Yii::t("app",
                    'Public key type should be one of: %s',
                    $type_list));
        }

        $public_key = new PhabricatorAuthSSHPublicKey();
        $public_key->type = $type;
        $public_key->body = $body;
        $public_key->comment = $comment;

        return $public_key;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHash()
    {
        $body = $this->getBody();
        $body = trim($body);
        $body = rtrim($body, '=');
        return PhabricatorHash::digestForIndex($body);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEntireKey()
    {
        $key = $this->type . ' ' . $this->body;
        if (strlen($this->comment)) {
            $key = $key . ' ' . $this->comment;
        }
        return $key;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws FilesystemException
     * @throws PhutilProxyException
     */
    public function toPKCS8()
    {
        $entire_key = $this->getEntireKey();
        $cache_key = $this->getPKCS8CacheKey($entire_key);

        $cache = PhabricatorCaches::getImmutableCache();
        $pkcs8_key = $cache->getKey($cache_key);
        if ($pkcs8_key) {
            return $pkcs8_key;
        }

        $tmp = new TempFile();
        Filesystem::writeFile($tmp, $this->getEntireKey());
        try {
            list($pkcs8_key) = execx(
                'ssh-keygen -e -m PKCS8 -f %s',
                $tmp);
        } catch (CommandException $ex) {
            unset($tmp);
            throw new PhutilProxyException(
                Yii::t("app",
                    'Failed to convert public key into PKCS8 format. If you are ' .
                    'developing on OSX, you may be able to use `%s` ' .
                    'to work around this issue. %s',
                    'bin/auth cache-pkcs8',
                    $ex->getMessage()),
                $ex);
        }
        unset($tmp);

        $cache->setKey($cache_key, $pkcs8_key);

        return $pkcs8_key;
    }

    /**
     * @param $pkcs8_key
     * @author 陈妙威
     * @throws Exception
     */
    public function forcePopulatePKCS8Cache($pkcs8_key)
    {
        $entire_key = $this->getEntireKey();
        $cache_key = $this->getPKCS8CacheKey($entire_key);

        $cache = PhabricatorCaches::getImmutableCache();
        $cache->setKey($cache_key, $pkcs8_key);
    }

    /**
     * @param $entire_key
     * @return string
     * @author 陈妙威
     */
    private function getPKCS8CacheKey($entire_key)
    {
        return 'pkcs8:' . PhabricatorHash::digestForIndex($entire_key);
    }

}
