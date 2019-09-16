<?php

namespace orangins\lib\infrastructure\cluster\search;

use PhutilURI;

/**
 * Class PhabricatorElasticsearchHost
 * @package orangins\lib\infrastructure\cluster\search
 * @author 陈妙威
 */
final class PhabricatorElasticsearchHost
    extends PhabricatorSearchHost
{

    /**
     * @var int
     */
    private $version = 5;
    /**
     * @var string
     */
    private $path = 'phabricator/';
    /**
     * @var string
     */
    private $protocol = 'http';

    /**
     *
     */
    const KEY_REFS = 'search.elastic.refs';


    /**
     * @param $config
     * @return $this
     * @author 陈妙威
     */
    public function setConfig($config)
    {
        $this->setRoles(idx($config, 'roles', $this->getRoles()))
            ->setHost(idx($config, 'host', $this->host))
            ->setPort(idx($config, 'port', $this->port))
            ->setProtocol(idx($config, 'protocol', $this->protocol))
            ->setPath(idx($config, 'path', $this->path))
            ->setVersion(idx($config, 'version', $this->version));
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        return pht('Elasticsearch');
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getStatusViewColumns()
    {
        return array(
            pht('Protocol') => $this->getProtocol(),
            pht('Host') => $this->getHost(),
            pht('Port') => $this->getPort(),
            pht('Index Path') => $this->getPath(),
            pht('Elastic Version') => $this->getVersion(),
            pht('Roles') => implode(', ', array_keys($this->getRoles())),
        );
    }

    /**
     * @param $protocol
     * @return $this
     * @author 陈妙威
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param $path
     * @return $this
     * @author 陈妙威
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $version
     * @return $this
     * @author 陈妙威
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param null $to_path
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function getURI($to_path = null)
    {
        $uri = (new PhutilURI('http://' . $this->getHost()))
            ->setProtocol($this->getProtocol())
            ->setPort($this->getPort())
            ->setPath($this->getPath());

        if ($to_path) {
            $uri->appendPath($to_path);
        }
        return $uri;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getConnectionStatus()
    {
        $status = $this->getEngine()->indexIsSane($this);
        return $status ? parent::STATUS_OKAY : parent::STATUS_FAIL;
    }

}
