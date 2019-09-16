<?php

namespace orangins\lib\infrastructure\util;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\modules\auth\models\PhabricatorAuthHMACKey;
use orangins\modules\cache\PhabricatorCaches;
use PhutilNumber;
use Exception;

/**
 * Class PhabricatorHash
 * @package orangins\lib\infrastructure\util
 * @author 陈妙威
 */
final class PhabricatorHash extends OranginsObject
{

    /**
     *
     */
    const INDEX_DIGEST_LENGTH = 12;
    /**
     *
     */
    const ANCHOR_DIGEST_LENGTH = 12;

    /**
     * Digest a string using HMAC+SHA1.
     *
     * Because a SHA1 collision is now known, this method should be considered
     * weak. Callers should prefer @{method:digestWithNamedKey}.
     *
     * @param $string
     * @param null $key
     * @return  string  32-byte hexadecimal SHA1+HMAC hash.
     * @throws Exception
     */
    public static function weakDigest($string, $key = null)
    {
        if ($key === null) {
            $key = PhabricatorEnv::getEnvConfig('security.hmac-key');
        }

        if (!$key) {
            throw new Exception(
                \Yii::t("app",
                    "Set a '{0}' in your Phabricator configuration!",
                    [
                        'security.hmac-key'
                    ]));
        }

        return hash_hmac('sha1', $string, $key);
    }


    /**
     * Digest a string for use in, e.g., a MySQL index. This produces a short
     * (12-byte), case-sensitive alphanumeric string with 72 bits of entropy,
     * which is generally safe in most contexts (notably, URLs).
     *
     * This method emphasizes compactness, and should not be used for security
     * related hashing (for general purpose hashing, see @{method:digest}).
     *
     * @param   string  Input string.
     * @return  string  12-byte, case-sensitive, mostly-alphanumeric hash of
     *                  the string.
     */
    public static function digestForIndex($string)
    {
        $hash = sha1($string, $raw_output = true);

        static $map;
        if ($map === null) {
            $map = '0123456789' .
                'abcdefghij' .
                'klmnopqrst' .
                'uvwxyzABCD' .
                'EFGHIJKLMN' .
                'OPQRSTUVWX' .
                'YZ._';
        }

        $result = '';
        for ($ii = 0; $ii < self::INDEX_DIGEST_LENGTH; $ii++) {
            $result .= $map[(ord($hash[$ii]) & 0x3F)];
        }

        return $result;
    }

    /**
     * Digest a string for use in HTML page anchors. This is similar to
     * @{method:digestForIndex} but produces purely alphanumeric output.
     *
     * This tries to be mostly compatible with the index digest to limit how
     * much stuff we're breaking by switching to it. For additional discussion,
     * see T13045.
     *
     * @param   string  Input string.
     * @return  string  12-byte, case-sensitive, purely-alphanumeric hash of
     *                  the string.
     */
    public static function digestForAnchor($string)
    {
        $hash = sha1($string, $raw_output = true);

        static $map;
        if ($map === null) {
            $map = '0123456789' .
                'abcdefghij' .
                'klmnopqrst' .
                'uvwxyzABCD' .
                'EFGHIJKLMN' .
                'OPQRSTUVWX' .
                'YZ';
        }

        $result = '';
        $accum = 0;
        $map_size = strlen($map);
        for ($ii = 0; $ii < self::ANCHOR_DIGEST_LENGTH; $ii++) {
            $byte = ord($hash[$ii]);
            $low_bits = ($byte & 0x3F);
            $accum = ($accum + $byte) % $map_size;

            if ($low_bits < $map_size) {
                // If an index digest would produce any alphanumeric character, just
                // use that character. This means that these digests are the same as
                // digests created with "digestForIndex()" in all positions where the
                // output character is some character other than "." or "_".
                $result .= $map[$low_bits];
            } else {
                // If an index digest would produce a non-alphumeric character ("." or
                // "_"), pick an alphanumeric character instead. We accumulate an
                // index into the alphanumeric character list to try to preserve
                // entropy here. We could use this strategy for all bytes instead,
                // but then these digests would differ from digests created with
                // "digestForIndex()" in all positions, instead of just a small number
                // of positions.
                $result .= $map[$accum];
            }
        }

        return $result;
    }


    /**
     * @param $string
     * @param $min
     * @param $max
     * @return int
     * @throws Exception
     * @author 陈妙威
     */
    public static function digestToRange($string, $min, $max)
    {
        if ($min > $max) {
            throw new Exception(\Yii::t("app",'Maximum must be larger than minimum.'));
        }

        if ($min == $max) {
            return $min;
        }

        $hash = sha1($string, $raw_output = true);
        // Make sure this ends up positive, even on 32-bit machines.
        $value = head(unpack('L', $hash)) & 0x7FFFFFFF;

        return $min + ($value % (1 + $max - $min));
    }


    /**
     * Shorten a string to a maximum byte length in a collision-resistant way
     * while retaining some degree of human-readability.
     *
     * This function converts an input string into a prefix plus a hash. For
     * example, a very long string beginning with "crabapplepie..." might be
     * digested to something like "crabapp-N1wM1Nz3U84k".
     *
     * This allows the maximum length of identifiers to be fixed while
     * maintaining a high degree of collision resistance and a moderate degree
     * of human readability.
     *
     * @param string The string to shorten.
     * @param int Maximum length of the result.
     * @return string String shortened in a collision-resistant way.
     * @throws Exception
     */
    public static function digestToLength($string, $length)
    {
        // We need at least two more characters than the hash length to fit in a
        // a 1-character prefix and a separator.
        $min_length = self::INDEX_DIGEST_LENGTH + 2;
        if ($length < $min_length) {
            throw new Exception(
                \Yii::t("app",
                    'Length parameter in {0} must be at least {1}, ' .
                    'but {2} was provided.',
                    [
                        'digestToLength()',
                        $min_length,
                        $length
                    ]));
        }

        // We could conceivably return the string unmodified if it's shorter than
        // the specified length. Instead, always hash it. This makes the output of
        // the method more recognizable and consistent (no surprising new behavior
        // once you hit a string longer than `$length`) and prevents an attacker
        // who can control the inputs from intentionally using the hashed form
        // of a string to cause a collision.

        $hash = self::digestForIndex($string);

        $prefix = substr($string, 0, ($length - ($min_length - 1)));

        return $prefix . '-' . $hash;
    }

    /**
     * @param $message
     * @param $key_name
     * @return string
     * @throws Exception
     * @throws \AphrontCountQueryException
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function digestWithNamedKey($message, $key_name)
    {
        $key_bytes = self::getNamedHMACKey($key_name);
        return self::digestHMACSHA256($message, $key_bytes);
    }

    /**
     * @param $message
     * @param $key
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function digestHMACSHA256($message, $key)
    {
        if (!strlen($key)) {
            throw new Exception(
                \Yii::t("app",'HMAC-SHA256 requires a nonempty key.'));
        }

        $result = hash_hmac('sha256', $message, $key, $raw_output = false);

        if ($result === false) {
            throw new Exception(
                \Yii::t("app",'Unable to compute HMAC-SHA256 digest of message.'));
        }

        return $result;
    }


    /* -(  HMAC Key Management  )------------------------------------------------ */


    /**
     * @param $hmac_name
     * @return string
     * @throws Exception
     * @throws \AphrontCountQueryException
     * @throws \FilesystemException
     * @author 陈妙威
     */
    private static function getNamedHMACKey($hmac_name)
    {
        $cache = PhabricatorCaches::getImmutableCache();

        $cache_key = "hmac.key({$hmac_name})";

        $hmac_key = $cache->getKey($cache_key);
        if (!strlen($hmac_key)) {
            $hmac_key = self::readHMACKey($hmac_name);

            if ($hmac_key === null) {
                $hmac_key = self::newHMACKey($hmac_name);
                self::writeHMACKey($hmac_name, $hmac_key);
            }

            $cache->setKey($cache_key, $hmac_key);
        }

        // The "hex2bin()" function doesn't exist until PHP 5.4.0 so just
        // implement it inline.
        $result = '';
        for ($ii = 0; $ii < strlen($hmac_key); $ii += 2) {
            $result .= pack('H*', substr($hmac_key, $ii, 2));
        }

        return $result;
    }

    /**
     * @param $hmac_name
     * @return string
     * @author 陈妙威
     * @throws \FilesystemException
     */
    private static function newHMACKey($hmac_name)
    {
        $hmac_key = Filesystem::readRandomBytes(64);
        return bin2hex($hmac_key);
    }

    /**
     * @param $hmac_name
     * @param $hmac_key
     * @author 陈妙威
     */
    private static function writeHMACKey($hmac_name, $hmac_key)
    {
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        (new PhabricatorAuthHMACKey())
            ->setKeyName($hmac_name)
            ->setKeyValue($hmac_key)
            ->save();

        unset($unguarded);
    }

    /**
     * @param $hmac_name
     * @return null
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private static function readHMACKey($hmac_name)
    {
        $row = PhabricatorAuthHMACKey::find()
            ->select(['key_value'])
            ->andWhere(['key_name' => $hmac_name])
            ->one();
        if (!$row) {
            return null;
        }
        return $row->key_value;
    }
}
