<?php

namespace orangins\aphront\site;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use PhutilClassMapQuery;
use PhutilURI;

/**
 * Class AphrontSite
 * @package orangins\aphront\site
 * @author 陈妙威
 */
abstract class AphrontSite extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPriority();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDescription();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function shouldRequireHTTPS();

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newSiteForRequest(AphrontRequest $request);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getRoutingMaps();

    /**
     * @param AphrontRequest $request
     * @return |null
     * @author 陈妙威
     */
    public function new404Controller(AphrontRequest $request)
    {
        return null;
    }

    /**
     * @param $host
     * @param array $uris
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    protected function isHostMatch($host, array $uris)
    {
        foreach ($uris as $uri) {
            if (!strlen($uri)) {
                continue;
            }

            $domain = (new PhutilURI($uri))->getDomain();

            if ($domain === $host) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return AphrontRoutingMap
     * @author 陈妙威
     */
    protected function newRoutingMap()
    {
        return (new AphrontRoutingMap())
            ->setSite($this);
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllSites()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setSortMethod('getPriority')
            ->execute();
    }

}
