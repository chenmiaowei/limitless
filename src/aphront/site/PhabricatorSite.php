<?php

namespace orangins\aphront\site;

use Exception;
use orangins\lib\env\PhabricatorEnv;

/**
 * Class PhabricatorSite
 * @package orangins\aphront\site
 * @author 陈妙威
 */
abstract class PhabricatorSite extends AphrontSite
{

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function shouldRequireHTTPS()
    {
        // If this is an intracluster request, it's okay for it to use HTTP even
        // if the site otherwise requires HTTPS. It is common to terminate SSL at
        // a load balancer and use plain HTTP from then on, and administrators are
        // usually not concerned about attackers observing traffic within a
        // datacenter.
        if (PhabricatorEnv::isClusterRemoteAddress()) {
            return false;
        }

        return PhabricatorEnv::getEnvConfig('security.require-https');
    }
}
