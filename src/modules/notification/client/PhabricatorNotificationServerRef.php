<?php

namespace orangins\modules\notification\client;

use HTTPFutureHTTPResponseStatus;
use HTTPSFuture;
use orangins\lib\OranginsObject;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\cache\PhabricatorCaches;
use Exception;
use PhutilURI;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorNotificationServerRef
 * @package orangins\modules\notification\client
 * @author 陈妙威
 */
final class PhabricatorNotificationServerRef
    extends OranginsObject
{

    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $host;
    /**
     * @var
     */
    private $port;
    /**
     * @var
     */
    private $protocol;
    /**
     * @var
     */
    private $path;
    /**
     * @var
     */
    private $isDisabled;

    /**
     *
     */
    const KEY_REFS = 'notification.refs';

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $host
     * @return $this
     * @author 陈妙威
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param $port
     * @return $this
     * @author 陈妙威
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPort()
    {
        return $this->port;
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
     * @return mixed
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
     * @return mixed
     * @author 陈妙威
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $is_disabled
     * @return $this
     * @author 陈妙威
     */
    public function setIsDisabled($is_disabled)
    {
        $this->isDisabled = $is_disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getLiveServers()
    {
        $cache = PhabricatorCaches::getRequestCache();

        $refs = $cache->getKey(self::KEY_REFS);
        if (!$refs) {
            $refs = self::newRefs();
            $cache->setKey(self::KEY_REFS, $refs);
        }

        return $refs;
    }

    /**
     * @return PhabricatorNotificationServerRef[]
     * @throws Exception
     * @author 陈妙威
     */
    public static function newRefs()
    {
        $configs = PhabricatorEnv::getEnvConfig('notification.servers');

        $refs = array();
        foreach ($configs as $config) {
            $ref = (new self())
                ->setType($config['type'])
                ->setHost($config['host'])
                ->setPort($config['port'])
                ->setProtocol($config['protocol'])
                ->setPath(ArrayHelper::getValue($config, 'path'))
                ->setIsDisabled(ArrayHelper::getValue($config, 'disabled', false));
            $refs[] = $ref;
        }

        return $refs;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getEnabledServers()
    {
        $servers = self::getLiveServers();

        foreach ($servers as $key => $server) {
            if ($server->getIsDisabled()) {
                unset($servers[$key]);
            }
        }

        return array_values($servers);
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getEnabledAdminServers()
    {
        $servers = self::getEnabledServers();

        foreach ($servers as $key => $server) {
            if (!$server->isAdminServer()) {
                unset($servers[$key]);
            }
        }

        return array_values($servers);
    }

    /**
     * @param $with_protocol
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getEnabledClientServers($with_protocol)
    {
        $servers = self::getEnabledServers();

        foreach ($servers as $key => $server) {
            if ($server->isAdminServer()) {
                unset($servers[$key]);
                continue;
            }

            $protocol = $server->getProtocol();
            if ($protocol != $with_protocol) {
                unset($servers[$key]);
                continue;
            }
        }

        return array_values($servers);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isAdminServer()
    {
        return ($this->type == 'admin');
    }

    /**
     * @param null $to_path
     * @return PhutilURI
     * @throws Exception
     * @author 陈妙威
     */
    public function getURI($to_path = null)
    {
        $full_path = rtrim($this->getPath(), '/') . '/' . ltrim($to_path, '/');

        $uri = (new PhutilURI('http://' . $this->getHost()))
            ->setProtocol($this->getProtocol())
            ->setPort($this->getPort())
            ->setPath($full_path);

        $instance = PhabricatorEnv::getEnvConfig('cluster.instance');
        if (strlen($instance)) {
            $uri->setQueryParam('instance', $instance);
        }

        return $uri;
    }

    /**
     * @param null $to_path
     * @return PhutilURI
     * @throws Exception
     * @author 陈妙威
     */
    public function getWebsocketURI($to_path = null)
    {
        $instance = PhabricatorEnv::getEnvConfig('cluster.instance');
        if (strlen($instance)) {
            $to_path = $to_path . '~' . $instance . '/';
        }

        $uri = $this->getURI($to_path);

        if ($this->getProtocol() == 'https') {
            $uri->setProtocol('wss');
        } else {
            $uri->setProtocol('ws');
        }

        return $uri;
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function testClient()
    {
        if ($this->isAdminServer()) {
            throw new Exception(
                \Yii::t("app",'Unable to test client on an admin server!'));
        }

        $server_uri = $this->getURI();

        try {
            (new HTTPSFuture($server_uri))
                ->setTimeout(2)
                ->resolvex();
        } catch (HTTPFutureHTTPResponseStatus $ex) {
            // This is what we expect when things are working correctly.
            if ($ex->getStatusCode() == 501) {
                return true;
            }
            throw $ex;
        }

        throw new Exception(
            \Yii::t("app",'Got HTTP 200, but expected HTTP 501 (WebSocket Upgrade)!'));
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function loadServerStatus()
    {
        if (!$this->isAdminServer()) {
            throw new Exception(
                \Yii::t("app",
                    'Unable to load server status: this is not an admin server!'));
        }

        $server_uri = $this->getURI('/status/');

        list($body) = (new HTTPSFuture($server_uri))
            ->setTimeout(2)
            ->resolvex();

        return phutil_json_decode($body);
    }

    /**
     * @param array $data
     * @throws Exception
     * @author 陈妙威
     */
    public function postMessage(array $data)
    {
        if (!$this->isAdminServer()) {
            throw new Exception(
                \Yii::t("app",'Unable to post message: this is not an admin server!'));
        }

        $server_uri = $this->getURI('/');
        $payload = phutil_json_encode($data);

        (new HTTPSFuture($server_uri, $payload))
            ->setMethod('POST')
            ->setTimeout(2)
            ->resolvex();
    }

}
