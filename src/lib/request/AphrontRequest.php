<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/21
 * Time: 11:46 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\request;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\AphrontMalformedRequestException;
use PhutilURI;
use orangins\lib\configuration\AphrontDefaultApplicationConfiguration;
use orangins\modules\people\models\PhabricatorUser;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Cookie;
use yii\web\Request;

/**
 * Class RequestHelper
 * @package orangins\lib\helpers
 * @author 陈妙威
 */
class AphrontRequest extends Request
{
    /**
     *
     */
    const TYPE_AJAX = '__ajax__';

    /**
     *
     */
    const TYPE_FORM = '__form__';
    /**
     *
     */
    const TYPE_CONDUIT = '__conduit__';
    /**
     *
     */
    const TYPE_WORKFLOW = '__wflow__';
    /**
     *
     */
    const TYPE_CONTINUE = '__continue__';
    /**
     *
     */
    const TYPE_PREVIEW = '__preview__';
    /**
     *
     */
    const TYPE_HISEC = '__hisec__';
    /**
     *
     */
    const TYPE_QUICKSAND = '__quicksand__';


    /**
     * @var PhabricatorUser
     */
    public $viewer;
    /**
     * @var
     */
    public $applicationConfiguration;

    /**
     * @var
     */
    private $cookiePrefix;

    /**
     * @var array
     */
    private $cookie = [];

    /**
     * RequestHelper constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->setApplicationConfiguration(new AphrontDefaultApplicationConfiguration());

        $cookieCollection = $this->getCookies();

        $cookies = [];
        foreach ($cookieCollection as $item) {
            $cookies[$item->name] = $item->value;
        }
        $this->cookie = $cookies;
    }

    /**
     * @param $prefix
     * @return $this
     * @author 陈妙威
     */
    public function setCookiePrefix($prefix)
    {
        $this->cookiePrefix = $prefix;
        return $this;
    }

    /**
     * @param $name
     * @return string
     * @author 陈妙威
     */
    private function getPrefixedCookieName($name)
    {
        if (strlen($this->cookiePrefix)) {
            return $this->cookiePrefix . '_' . $name;
        } else {
            return $name;
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     * @author 陈妙威
     */
    public function getCookie($name, $default = null)
    {
        $name = $this->getPrefixedCookieName($name);
        $value = ArrayHelper::getValue($this->cookie, $name, $default);

        // Internally, PHP deletes cookies by setting them to the value 'deleted'
        // with an expiration date in the past.

        // At least in Safari, the browser may send this cookie anyway in some
        // circumstances. After logging out, the 302'd GET to /login/ consistently
        // includes deleted cookies on my local install. If a cookie value is
        // literally 'deleted', pretend it does not exist.

        if ($value === 'deleted') {
            return null;
        }

        return $value;
    }

    /**
     * @param $name
     * @param $value
     * @return self
     * @throws \yii\base\Exception
     */
    public function setCookie($name, $value)
    {
        $far_future = time() + (60 * 60 * 24 * 365 * 5);
        return $this->setCookieWithExpiration($name, $value, $far_future);
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function clearCookie($name)
    {
        $this->setCookieWithExpiration($name, '', time() - (60 * 60 * 24 * 30));
        unset($this->cookie[$name]);
        return $this;

    }

    /**
     * Set a cookie which expires soon.
     *
     * To set a durable cookie, see @{method:setCookie}.
     *
     * @param string  Cookie name.
     * @param string  Cookie value.
     * @return static
     * @task cookie
     * @throws \yii\base\Exception
     */
    public function setTemporaryCookie($name, $value)
    {
        return $this->setCookieWithExpiration($name, $value, time() + (60 * 60));
    }


    /**
     * Set a cookie with a given expiration policy.
     *
     * @param string  Cookie name.
     * @param string  Cookie value.
     * @param int     Epoch timestamp for cookie expiration.
     * @return static
     * @task cookie
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function setCookieWithExpiration(
        $name,
        $value,
        $expire)
    {

        $is_secure = false;

        $base_domain_uri = $this->getCookieDomainURI();
        if (!$base_domain_uri) {
            $configured_as = PhabricatorEnv::getEnvConfig('orangins.base-uri');
            $accessed_as = $this->getHostInfo();

            throw new AphrontMalformedRequestException(
                Yii::t('app', 'Bad Host Header'),
                Yii::t('app',
                    'This {0} install is configured as "{1}", but you are ' .
                    'using the domain name "{2}" to access a page which is trying to ' .
                    'set a cookie. Access Phabricator on the configured primary ' .
                    'domain or a configured alternate domain. Phabricator will not ' .
                    'set cookies on other domains for security reasons.',
                    [
                        PhabricatorEnv::getEnvConfig("orangins.site-name"),
                        $configured_as,
                        $accessed_as
                    ]),
                true);
        }

        $base_domain = $base_domain_uri->getDomain();
        $is_secure = ($base_domain_uri->getProtocol() == 'https');

        $name = $this->getPrefixedCookieName($name);

        if (php_sapi_name() == 'cli') {
            // Do nothing, to avoid triggering "Cannot modify header information"
            // warnings.

            // TODO: This is effectively a test for whether we're running in a unit
            // test or not. Move this actual call to HTTPSink?
        } else {

            $cookies = Yii::$app->response->cookies;
            // add a new cookie to the response to be sent
            $cookies->add(new Cookie([
                'name' => $name,
                'value' => $value,
                'expire' => $expire,
                'path' => $path = '/',
                'httpOnly' => true,
                'secure' => $is_secure,
//                'domain' => $base_domain,
            ]));
        }
        $this->cookie[$name] = $value;
        return $this;
    }


    /**
     * @param $application_configuration
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationConfiguration(
        $application_configuration)
    {
        $this->applicationConfiguration = $application_configuration;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationConfiguration()
    {
        return $this->applicationConfiguration;
    }

    /**
     * @task data
     * @param $name
     * @return bool
     */
    public function getExists($name)
    {
        return array_key_exists($name, $this->getRequestData());
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isAjax()
    {
        return $this->getExists(self::TYPE_AJAX) && !$this->isQuicksand();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isWorkflow()
    {
        return $this->getExists(self::TYPE_WORKFLOW) && !$this->isQuicksand();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isQuicksand()
    {
        return $this->getExists(self::TYPE_QUICKSAND);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isConduit()
    {
        return $this->getExists(self::TYPE_CONDUIT);
    }

    /**
     * @task data
     * @param $name
     * @param null $default
     * @return int|null
     */
    public function getInt($name, $default = null)
    {
        if (isset($this->requestData[$name])) {
            // Converting from array to int is "undefined". Don't rely on whatever
            // PHP decides to do.
            if (is_array($this->getRequestData()[$name])) {
                return $default;
            }
            return (int)$this->getRequestData()[$name];
        } else {
            return $default;
        }
    }


    /**
     * @task data
     * @param $name
     * @param null $default
     * @return bool|null
     */
    public function getBool($name, $default = null)
    {
        if (isset($this->requestData[$name])) {
            if ($this->getRequestData()[$name] === 'true') {
                return true;
            } else if ($this->getRequestData()[$name] === 'false') {
                return false;
            } else {
                return (bool)$this->getRequestData()[$name];
            }
        } else {
            return $default;
        }
    }


    /**
     * @task data
     * @param $name
     * @param null $default
     * @return mixed|null|string
     */
    public function getStr($name, $default = null)
    {
        if (isset($this->requestData[$name])) {
            $str = (string)$this->getRequestData()[$name];
            // Normalize newline craziness.
            $str = str_replace(
                array("\r\n", "\r"),
                array("\n", "\n"),
                $str);
            return $str;
        } else {
            return $default;
        }
    }


    /**
     * @task data
     * @param $name
     * @param array $default
     * @return array
     */
    public function getArr($name, $default = array())
    {
        if (isset($this->requestData[$name]) &&
            is_array($this->getRequestData()[$name])) {
            return $this->getRequestData()[$name];
        } else {
            return $default;
        }
    }


    /**
     * @task data
     * @param $name
     * @param array $default
     * @return array|array[]|false|mixed|null|string|string[]
     */
    public function getStrList($name, $default = array())
    {
        if (!isset($this->requestData[$name])) {
            return $default;
        }
        $list = $this->getStr($name);
        $list = preg_split('/[\s,]+/', $list, $limit = -1, PREG_SPLIT_NO_EMPTY);
        return $list;
    }


    /**
     * @param $name
     * @return bool
     * @author 陈妙威
     */
    public function getFileExists($name)
    {
        return isset($_FILES[$name]) &&
            (ArrayHelper::getValue($_FILES[$name], 'error') !== UPLOAD_ERR_NO_FILE);
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return ArrayHelper::merge($this->get(), $this->post());
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getRequestURI()
    {
        $url = \Yii::$app->request->url;
        $phutilURI = new PhutilURI($url);
        return $phutilURI;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getUerHostIP()
    {
        if (isset($_SERVER['X_FORWARDED_FOR'])) {
            $realip = $_SERVER['X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $realip = $_SERVER['REMOTE_ADDR'];
        } else {
            $realip = "";
        }
        return $realip;
    }


    /**
     * Return if the request is sent via secure channel (https).
     * @return bool if the request is sent via secure channel (https)
     */
    public function getIsSecureConnection()
    {
        //华为云https判断
        foreach ($this->secureProtocolHeaders as $header => $values) {
            $header = 'HTTP_' . str_replace('-', "_", strtoupper($header));
            if (isset($_SERVER[$header])) {
                foreach ($values as $value) {
                    if (strcasecmp($_SERVER[$header], $value) === 0) {
                        return true;
                    }
                }
            }
        }
        if ($_SERVER['SERVER_PORT'] === '8443') {
            return true;
        }
        return parent::getIsSecureConnection();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isFormPost()
    {
        $post = $this->getExists(self::TYPE_FORM) &&
            !$this->getExists(self::TYPE_HISEC) &&
            $this->getIsPost();

        if (!$post) {
            return false;
        }
        return true;
    }

    /**
     * @param $key
     * @param null $default
     * @return string
     * @author 陈妙威
     */
    public function getURIData($key, $default = null)
    {
        return $this->get($key, $default);
    }

    /**
     * @return PhabricatorUser
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorUser $user
     * @return self
     */
    public function setViewer($user)
    {
        $this->viewer = $user;
        return $this;
    }


    /**
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getAbsoluteRequestURI()
    {
        $uri = $this->getRequestURI();
        $uri->setDomain($this->getHost());

        if ($this->getIsSecureConnection()) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        $uri->setProtocol($protocol);

        // If the request used a nonstandard port, preserve it while building the
        // absolute URI.

        // First, get the default port for the request protocol.
        $default_port = (new PhutilURI($protocol . '://example.com/'))
            ->getPortWithProtocolDefault();

        // NOTE: See note in getHost() about malicious "Host" headers. This
        // construction defuses some obscure potential attacks.
        $port = (new PhutilURI($protocol . '://' . $this->getHost()))
            ->getPort();

        if (($port !== null) && ($port !== $default_port)) {
            $uri->setPort($port);
        }

        return $uri;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isHTTPGet()
    {
        return $this->getIsGet();
    }


    /**
     * Get request data other than "magic" parameters.
     *
     * @param bool $include_quicksand
     * @return array<string, wild> Request data, with magic filtered out.
     */
    public function getPassthroughRequestData($include_quicksand = false)
    {
        $data = $this->post();

        // Remove magic parameters like __dialog__ and __ajax__.
        foreach ($data as $key => $value) {
            if ($include_quicksand && $key == self::TYPE_QUICKSAND) {
                continue;
            }
            if (!strncmp($key, '__', 2)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getPath()
    {
        return $this->getPathInfo();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDialogFormPost()
    {
        return $this->isFormPost() && $this->getStr('__dialog__');
    }


    /**
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    public function getHost()
    {
        // The "Host" header may include a port number, or may be a malicious
        // header in the form "realdomain.com:ignored@evil.com". Invoke the full
        // parser to extract the real domain correctly. See here for coverage of
        // a similar issue in Django:
        //
        //  https://www.djangoproject.com/weblog/2012/oct/17/security/
        $pathInfo = $this->getHostInfo();
        $uri = new PhutilURI($pathInfo);
        return $uri->getDomain();
    }

    /**
     * Get the domain which cookies should be set on for this request, or null
     * if the request does not correspond to a valid cookie domain.
     *
     * @return PhutilURI|null   Domain URI, or null if no valid domain exists.
     *
     * @task cookie
     * @throws \Exception
     */
    private function getCookieDomainURI()
    {
        if (PhabricatorEnv::getEnvConfig('security.require-https') &&
            !$this->getIsSecureConnection()) {
            return null;
        }

        $host = $this->getHost();

        // If there's no base domain configured, just use whatever the request
        // domain is. This makes setup easier, and we'll tell administrators to
        // configure a base domain during the setup process.
        $base_uri = PhabricatorEnv::getEnvConfig('orangins.base-uri');
        if (!strlen($base_uri)) {
            return new PhutilURI($host);
        }

        $alternates = PhabricatorEnv::getEnvConfig('orangins.allowed-uris');
        $allowed_uris = array_merge(array($base_uri), $alternates);

        foreach ($allowed_uris as $allowed_uri) {
            $uri = new PhutilURI($allowed_uri);

            if ($uri->getDomain() == $host) {
                return $uri;
            }
        }

        return null;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isHTTPPost()
    {
        return $this->getIsPost();
    }


    /**
     * Read line range parameter data from the request.
     *
     * Applications like Paste, Diffusion, and Harbormaster use "$12-14" in the
     * URI to allow users to link to particular lines.
     *
     * @param string URI data key to pull line range information from.
     * @param int|null Maximum length of the range.
     * @return array
     */
    public function getURILineRange($key, $limit)
    {
        $range = $this->getURIData($key);
        return self::parseURILineRange($range, $limit);
    }


    /**
     * @param $range
     * @param $limit
     * @return array|null
     * @author 陈妙威
     */
    public static function parseURILineRange($range, $limit)
    {
        if (!strlen($range)) {
            return null;
        }

        $range = explode('-', $range, 2);

        foreach ($range as $key => $value) {
            $value = (int)$value;
            if (!$value) {
                // If either value is "0", discard the range.
                return null;
            }
            $range[$key] = $value;
        }

        // If the range is like "$10", treat it like "$10-10".
        if (count($range) == 1) {
            $range[] = head($range);
        }

        // If the range is "$7-5", treat it like "$5-7".
        if ($range[1] < $range[0]) {
            $range = array_reverse($range);
        }

        // If the user specified something like "$1-999999999" and we have a limit,
        // clamp it to a more reasonable range.
        if ($limit !== null) {
            if ($range[1] - $range[0] > $limit) {
                $range[1] = $range[0] + $limit;
            }
        }

        return $range;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isContinueRequest()
    {
        return $this->isFormPost() && $this->getStr('__continue__');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPreviewRequest()
    {
        return $this->isFormPost() && $this->getStr('__preview__');
    }

    /**
     * @param $transactions_key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getValue($transactions_key, $default = null)
    {
        return ArrayHelper::getValue($this->getRequestData(), $transactions_key, $default);
    }
}