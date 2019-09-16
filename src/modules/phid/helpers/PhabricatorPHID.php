<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/7
 * Time: 2:35 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\phid\helpers;


use orangins\lib\helpers\OranginsUtil;
use orangins\modules\file\FilesystemException;
use orangins\modules\phid\PhabricatorPHIDConstants;
use Exception;
use Yii;

/**
 * Class PhabricatorPHID
 * @package orangins\modules\phid\helpers
 * @author 陈妙威
 */
class PhabricatorPHID
{

    /**
     * Look up the type of a PHID. Returns
     * PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN if it fails to look up the type
     *
     * @param  string Anything.
     * @return  string A value from PhabricatorPHIDConstants (ideally)
     */
    public static function phid_get_type($phid)
    {
        $matches = null;
        if (is_string($phid) && preg_match('/^PHID-([^-]{4})-/', $phid, $matches)) {
            return $matches[1];
        }
        return PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN;
    }

    /**
     * Group a list of phids by type.
     *
     * @param  string[] array of phids
     * @return array of phid type => list of phids
     */
    public static function phid_group_by_type($phids)
    {
        $result = array();
        foreach ($phids as $phid) {
            $type = self::phid_get_type($phid);
            $result[$type][] = $phid;
        }
        return $result;
    }

    /**
     * @param $phid
     * @return bool|null|string
     * @author 陈妙威
     */
    public static function phid_get_subtype($phid)
    {
        if (isset($phid[14]) && ($phid[14] == '-')) {
            return substr($phid, 10, 4);
        }
        return null;
    }


    /**
     * @param $type
     * @param null $subtype
     * @return string
     * @throws Exception
     */
    public static function generateNewPHID($type, $subtype = null)
    {
        if (!$type) {
            throw new Exception(200, Yii::t('app', 'Can not generate PHID with no type.'));
        }

        if ($subtype === null) {
            $uniq_len = 20;
            $type_str = "{$type}";
        } else {
            $uniq_len = 15;
            $type_str = "{$type}-{$subtype}";
        }

        $uniq = self::readRandomCharacters($uniq_len);
        return "PHID-{$type_str}-{$uniq}";
    }


    /**
     * Read random alphanumeric characters from /dev/urandom or equivalent. This
     * method operates like @{method:readRandomBytes} but produces alphanumeric
     * output (a-z, 0-9) so it's appropriate for use in URIs and other contexts
     * where it needs to be human readable.
     *
     * @param   int     Number of characters to read.
     * @return  string  Random character string of the provided length.
     *
     * @task file
     * @throws Exception
     */
    public static function readRandomCharacters($number_of_characters)
    {

        // NOTE: To produce the character string, we generate a random byte string
        // of the same length, select the high 5 bits from each byte, and
        // map that to 32 alphanumeric characters. This could be improved (we
        // could improve entropy per character with base-62, and some entropy
        // sources might be less entropic if we discard the low bits) but for
        // reasonable cases where we have a good entropy source and are just
        // generating some kind of human-readable secret this should be more than
        // sufficient and is vastly simpler than trying to do bit fiddling.

        $map = array_merge(range('a', 'z'), range('2', '7'));

        $result = '';
        $bytes = self::readRandomBytes($number_of_characters);
        for ($ii = 0; $ii < $number_of_characters; $ii++) {
            $result .= $map[ord($bytes[$ii]) >> 3];
        }

        return $result;
    }

    /**
     * Read random bytes from /dev/urandom or equivalent. See also
     * @{method:readRandomCharacters}.
     *
     * @param   int     Number of bytes to read.
     * @return  string  Random bytestring of the provided length.
     *
     * @task file
     * @throws Exception
     */
    public static function readRandomBytes($number_of_bytes)
    {
        $number_of_bytes = (int)$number_of_bytes;
        if ($number_of_bytes < 1) {
            throw new Exception(Yii::t('app', 'You must generate at least 1 byte of entropy.'));
        }

        // Try to use `openssl_random_pseudo_bytes()` if it's available. This source
        // is the most widely available source, and works on Windows/Linux/OSX/etc.

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = true;
            $data = openssl_random_pseudo_bytes($number_of_bytes, $strong);

            if (!$strong) {
                // NOTE: This indicates we're using a weak random source. This is
                // probably OK, but maybe we should be more strict here.
            }

            if ($data === false) {
                throw new Exception(
                    Yii::t('app',
                        '{0} failed to generate entropy!',
                        [
                            'openssl_random_pseudo_bytes()'
                        ]
                    ));
            }

            if (strlen($data) != $number_of_bytes) {
                throw new Exception(
                    Yii::t('app',
                        '{0} returned an unexpected number of bytes (got {1}, expected {2})!',
                        [
                            'openssl_random_pseudo_bytes()',
                            (strlen($data)),
                            ($number_of_bytes)
                        ]
                    ));
            }

            return $data;
        }


        // Try to use `/dev/urandom` if it's available. This is usually available
        // on non-Windows systems, but some PHP config (open_basedir) and chrooting
        // may limit our access to it.

        $urandom = @fopen('/dev/urandom', 'rb');
        if ($urandom) {
            $data = @fread($urandom, $number_of_bytes);
            @fclose($urandom);
            if (strlen($data) != $number_of_bytes) {
                throw new FilesystemException(
                    '/dev/urandom',
                    Yii::t('app', 'Failed to read random bytes!'));
            }
            return $data;
        }

        // (We might be able to try to generate entropy here from a weaker source
        // if neither of the above sources panned out, see some discussion in
        // T4153.)

        // We've failed to find any valid entropy source. Try to fail in the most
        // useful way we can, based on the platform.

        if (phutil_is_windows()) {
            throw new Exception(
                Yii::t('app',
                    '{0} requires the PHP OpenSSL extension to be installed and enabled ' .
                    'to access an entropy source. On Windows, this extension is usually ' .
                    'installed but not enabled by default. Enable it in your "{1}".',
                    [
                        __METHOD__ . '()',
                        'php.ini'
                    ]
                )
            );
        }

        throw new Exception(
            Yii::t('app',
                '{0} requires the PHP OpenSSL extension or access to "{1}". Install or ' .
                'enable the OpenSSL extension, or make sure "{2}" is accessible.',
                [
                    __METHOD__ . '()',
                    '/dev/urandom',
                    '/dev/urandom'
                ]
            ));
    }

}