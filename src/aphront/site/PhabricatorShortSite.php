<?php

namespace orangins\aphront\site;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;

/**
 * Class PhabricatorShortSite
 * @package orangins\aphront\site
 * @author 陈妙威
 */
final class PhabricatorShortSite extends PhabricatorSite
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return pht('Serves shortened URLs.');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getPriority()
    {
        return 2500;
    }

    /**
     * @param AphrontRequest $request
     * @return bool|PhabricatorShortSite|null
     * @throws \Exception
     * @author 陈妙威
     */
    public function newSiteForRequest(AphrontRequest $request)
    {
        $host = $request->getHost();

        $uri = PhabricatorEnv::getEnvConfig('phurl.short-uri');
        if (!strlen($uri)) {
            return null;
        }

        $phurl_installed = PhabricatorApplication::isClassInstalled(
            'PhabricatorPhurlApplication');
        if (!$phurl_installed) {
            return false;
        }

        if ($this->isHostMatch($host, array($uri))) {
            return new PhabricatorShortSite();
        }

        return null;
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getRoutingMaps()
    {
        $app = PhabricatorApplication::getByClass('PhabricatorPhurlApplication');

        $maps = array();
        $maps[] = $this->newRoutingMap()
            ->setApplication($app)
            ->setRoutes($app->getShortRoutes());
        return $maps;
    }

}
