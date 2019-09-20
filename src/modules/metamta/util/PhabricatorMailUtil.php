<?php

namespace orangins\modules\metamta\util;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilEmailAddress;

/**
 * Class PhabricatorMailUtil
 * @package orangins\modules\metamta\util
 * @author 陈妙威
 */
final class PhabricatorMailUtil
    extends OranginsObject
{

    /**
     * Normalize an email address for comparison or lookup.
     *
     * Phabricator can be configured to prepend a prefix to all reply addresses,
     * which can make forwarding rules easier to write. This method strips the
     * prefix if it is present, and normalizes casing and whitespace.
     *
     * @param PhutilEmailAddress $address
     * @return PhutilEmailAddress Normalized address.
     * @throws \Exception
     */
    public static function normalizeAddress(PhutilEmailAddress $address)
    {
        $raw_address = $address->getAddress();
        $raw_address = phutil_utf8_strtolower($raw_address);
        $raw_address = trim($raw_address);

        // If a mailbox prefix is configured and present, strip it off.
        $prefix_key = 'metamta.single-reply-handler-prefix';
        $prefix = PhabricatorEnv::getEnvConfig($prefix_key);
        $len = strlen($prefix);

        if ($len) {
            $prefix = $prefix . '+';
            $len = $len + 1;

            if (!strncasecmp($raw_address, $prefix, $len)) {
                $raw_address = substr($raw_address, $len);
            }
        }

        return id(clone $address)
            ->setAddress($raw_address);
    }

    /**
     * Determine if two inbound email addresses are effectively identical.
     *
     * This method strips and normalizes addresses so that equivalent variations
     * are correctly detected as identical. For example, these addresses are all
     * considered to match one another:
     *
     *   "Abraham Lincoln" <alincoln@example.com>
     *   alincoln@example.com
     *   <ALincoln@example.com>
     *   "Abraham" <phabricator+ALINCOLN@EXAMPLE.COM> # With configured prefix.
     *
     * @param PhutilEmailAddress $u
     * @param PhutilEmailAddress $v
     * @return  bool True if addresses are effectively the same address.
     * @throws \Exception
     */
    public static function matchAddresses(
        PhutilEmailAddress $u,
        PhutilEmailAddress $v)
    {

        $u = self::normalizeAddress($u);
        $v = self::normalizeAddress($v);

        return ($u->getAddress() === $v->getAddress());
    }

    /**
     * @param PhutilEmailAddress $address
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public static function isReservedAddress(PhutilEmailAddress $address)
    {
        $address = self::normalizeAddress($address);
        $local = $address->getLocalPart();

        $reserved = array(
            'admin',
            'administrator',
            'hostmaster',
            'list',
            'list-request',
            'majordomo',
            'postmaster',
            'root',
            'ssl-admin',
            'ssladmin',
            'ssladministrator',
            'sslwebmaster',
            'sysadmin',
            'uucp',
            'webmaster',

            'noreply',
            'no-reply',
        );

        $reserved = array_fuse($reserved);

        if (isset($reserved[$local])) {
            return true;
        }

        $default_address = (new PhabricatorMailEmailEngine())
            ->newDefaultEmailAddress();
        if (self::matchAddresses($address, $default_address)) {
            return true;
        }

        $void_address = id(new PhabricatorMailEmailEngine())
            ->newVoidEmailAddress();
        if (self::matchAddresses($address, $void_address)) {
            return true;
        }

        return false;
    }

    /**
     * @param PhutilEmailAddress $address
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function isUserAddress(PhutilEmailAddress $address)
    {
        $user_email = PhabricatorUserEmail::find()->andWhere(['address' => $address->getAddress()])->exists();
        return (bool)$user_email;
    }
}
