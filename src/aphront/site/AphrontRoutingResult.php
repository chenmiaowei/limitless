<?php

namespace orangins\aphront\site;

use orangins\lib\controllers\PhabricatorController;
use orangins\lib\OranginsObject;
use orangins\lib\PhabricatorApplication;

/**
 * Details about a routing map match for a path.
 *
 * @param info Result Information
 */
final class AphrontRoutingResult extends OranginsObject
{

    /**
     * @var
     */
    private $site;
    /**
     * @var
     */
    private $application;
    /**
     * @var
     */
    private $controller;
    /**
     * @var
     */
    private $uriData;


    /* -(  Result Information  )------------------------------------------------- */


    /**
     * @param AphrontSite $site
     * @return $this
     * @author 陈妙威
     */
    public function setSite(AphrontSite $site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param PhabricatorApplication $application
     * @return $this
     * @author 陈妙威
     */
    public function setApplication(PhabricatorApplication $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param PhabricatorController $controller
     * @return $this
     * @author 陈妙威
     */
    public function setController(PhabricatorController $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param array $uri_data
     * @return $this
     * @author 陈妙威
     */
    public function setURIData(array $uri_data)
    {
        $this->uriData = $uri_data;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getURIData()
    {
        return $this->uriData;
    }

}
