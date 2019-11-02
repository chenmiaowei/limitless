<?php

namespace orangins\aphront\site;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;

/**
 * Class PhabricatorResourceSite
 * @package orangins\aphront\site
 * @author 陈妙威
 */
final class PhabricatorResourceSite extends PhabricatorSite
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return pht('Serves static resources like images, CSS and JS.');
    }

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getPriority()
    {
        return 2000;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed|PhabricatorResourceSite|null
     * @throws \Exception
     * @author 陈妙威
     */
    public function newSiteForRequest(AphrontRequest $request)
    {
        $host = $request->getHost();

        $uri = PhabricatorEnv::getEnvConfig('security.alternate-file-domain');
        if (!strlen($uri)) {
            return null;
        }

        if ($this->isHostMatch($host, array($uri))) {
            return new PhabricatorResourceSite();
        }

        return null;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getRoutingMaps()
    {
        $applications = PhabricatorApplication::getAllInstalledApplications();

        $maps = array();
        foreach ($applications as $application) {
            $maps[] = $this->newRoutingMap()
                ->setApplication($application)
                ->setRoutes($application->getResourceRoutes());
        }

        return $maps;
    }

}
