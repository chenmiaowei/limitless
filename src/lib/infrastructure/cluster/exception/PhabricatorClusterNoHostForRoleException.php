<?php

namespace orangins\lib\infrastructure\cluster\exception;

use Exception;

/**
 * Class PhabricatorClusterNoHostForRoleException
 * @package orangins\lib\infrastructure\cluster\exception
 * @author 陈妙威
 */
final class PhabricatorClusterNoHostForRoleException
    extends Exception
{

    /**
     * PhabricatorClusterNoHostForRoleException constructor.
     * @param $role
     */
    public function __construct($role)
    {
        parent::__construct(pht('Search cluster has no hosts for role "%s".',
            $role));
    }
}
