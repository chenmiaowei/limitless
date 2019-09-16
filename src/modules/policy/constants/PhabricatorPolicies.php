<?php

namespace orangins\modules\policy\constants;

use orangins\lib\env\PhabricatorEnv;

/**
 * Class PhabricatorPolicies
 * @package orangins\modules\policy\constants
 * @author 陈妙威
 */
final class PhabricatorPolicies extends PhabricatorPolicyConstants
{

    /**
     *
     */
    const POLICY_PUBLIC = 'public';
    /**
     *
     */
    const POLICY_USER = 'users';
    /**
     *
     */
    const POLICY_ADMIN = 'admin';
    /**
     *
     */
    const POLICY_NOONE = 'no-one';

    /**
     * Returns the most public policy this install's configuration permits.
     * This is either "public" (if available) or "all users" (if not).
     *
     * @return string
     * @throws \Exception
     */
    public static function getMostOpenPolicy()
    {
        if (PhabricatorEnv::getEnvConfig('policy.allow-public')) {
            return self::POLICY_PUBLIC;
        } else {
            return self::POLICY_USER;
        }
    }
}
