<?php

namespace orangins\modules\conduit\protocol;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\OranginsObject;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerAccessToken;
use orangins\modules\people\models\PhabricatorUser;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class ConduitAPIRequest
 * @package orangins\modules\conduit\protocol
 * @author 陈妙威
 */
final class ConduitAPIRequest extends OranginsObject
{

    /**
     * @var array
     */
    protected $params;
    /**
     * @var
     */
    private $user;
    /**
     * @var bool
     */
    private $isClusterRequest = false;
    /**
     * @var
     */
    private $oauthToken;
    /**
     * @var bool
     */
    private $isStrictlyTyped = true;

    /**
     * ConduitAPIRequest constructor.
     * @param array $params
     * @param $strictly_typed
     */
    public function __construct(array $params, $strictly_typed)
    {
        $this->params = $params;
        $this->isStrictlyTyped = $strictly_typed;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getValue($key, $default = null)
    {
        return coalesce(ArrayHelper::getValue($this->params, $key), $default);
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function getValueExists($key)
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAllParameters()
    {
        return $this->params;
    }

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser(PhabricatorUser $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Retrieve the authentic identity of the user making the request. If a
     * method requires authentication (the default) the user object will always
     * be available. If a method does not require authentication (i.e., overrides
     * shouldRequireAuthentication() to return false) the user object will NEVER
     * be available.
     *
     * @return PhabricatorUser Authentic user, available ONLY if the method
     *                         requires authentication.
     * @throws Exception
     */
    public function getUser()
    {
        if (!$this->user) {
            throw new Exception(
                \Yii::t("app",
                    'You can not access the user inside the implementation of a Conduit ' .
                    'method which does not require authentication (as per %s).',
                    'shouldRequireAuthentication()'));
        }
        return $this->user;
    }

    /**
     * @param PhabricatorOAuthServerAccessToken $oauth_token
     * @return $this
     * @author 陈妙威
     */
    public function setOAuthToken(
        PhabricatorOAuthServerAccessToken $oauth_token)
    {
        $this->oauthToken = $oauth_token;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOAuthToken()
    {
        return $this->oauthToken;
    }

    /**
     * @param $is_cluster_request
     * @return $this
     * @author 陈妙威
     */
    public function setIsClusterRequest($is_cluster_request)
    {
        $this->isClusterRequest = $is_cluster_request;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsClusterRequest()
    {
        return $this->isClusterRequest;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsStrictlyTyped()
    {
        return $this->isStrictlyTyped;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function newContentSource()
    {
        return PhabricatorContentSource::newForSource(
            PhabricatorConduitContentSource::SOURCECONST);
    }

}
