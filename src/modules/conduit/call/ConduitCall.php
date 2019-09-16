<?php

namespace orangins\modules\conduit\call;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\exception\ConduitApplicationNotInstalledException;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\conduit\protocol\exception\ConduitMethodDoesNotExistException;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\userservice\conduitprice\UserServiceConduitPrice;
use PhutilServiceProfiler;

/**
 * Run a conduit method in-process, without requiring HTTP requests. Usage:
 *
 *   $call = new ConduitCall('method.name', array('param' => 'value'));
 *   $call->setUser($user);
 *   $result = $call->execute();
 *
 */
final class ConduitCall extends OranginsObject
{

    /**
     * @var
     */
    private $method;
    /**
     * @var ConduitAPIMethod
     */
    private $handler;
    /**
     * @var ConduitAPIRequest
     */
    private $request;
    /**
     * @var
     */
    private $user;

    /**
     * ConduitCall constructor.
     * @param $method
     * @param array $params
     * @param bool $strictly_typed
     * @throws ConduitException
     * @throws \yii\base\Exception
     */
    public function __construct($method, array $params, $strictly_typed = true)
    {
        $this->method = $method;
        $this->handler = $this->buildMethodHandler($method);

        $param_types = $this->handler->getParamTypes();

        foreach ($param_types as $key => $spec) {
            if (ConduitAPIMethod::getParameterMetadataKey($key) !== null) {
                throw new ConduitException(
                    \Yii::t("app",
                        'API Method "{0}" defines a disallowed parameter, "{1}". This ' .
                        'parameter name is reserved.', [
                            $method,
                            $key
                        ]));
            }
        }

        $invalid_params = array_diff_key($params, $param_types);
        if ($invalid_params) {
            throw new ConduitException(
                \Yii::t("app",
                    'API Method "{0}" does not define these parameters: {1}.', [
                        $method,
                        "'" . implode("', '", array_keys($invalid_params)) . "'"
                    ]));
        }

        $this->request = new ConduitAPIRequest($params, $strictly_typed);
    }

    /**
     * @return ConduitAPIMethod
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param ConduitAPIMethod $handler
     * @return self
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return ConduitAPIRequest
     * @author 陈妙威
     */
    public function getAPIRequest()
    {
        return $this->request;
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
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAuthentication()
    {
        return $this->handler->shouldRequireAuthentication();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldPayFee()
    {
        return $this->handler->shouldPayFee();
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function shouldAllowUnguardedWrites()
    {
        return $this->handler->shouldAllowUnguardedWrites();
    }

    /**
     * @param $code
     * @return mixed
     * @author 陈妙威
     */
    public function getErrorDescription($code)
    {
        return $this->handler->getErrorDescription($code);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function execute()
    {
        $profiler = PhutilServiceProfiler::getInstance();
        $call_id = $profiler->beginServiceCall(
            array(
                'type' => 'conduit',
                'method' => $this->method,
            ));

        try {
            $result = $this->executeMethod();
        } catch (Exception $ex) {
            $profiler->endServiceCall($call_id, array());
            throw $ex;
        }

        $profiler->endServiceCall($call_id, array());
        return $result;
    }

    /**
     * @return mixed
     * @throws ConduitException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function executeMethod()
    {
        $user = $this->getUser();
        if (!$user) {
            $user = new PhabricatorUser();
        }

        $this->request->setUser($user);

        if (!$this->shouldRequireAuthentication()) {
            // No auth requirement here.
        } else {

            $allow_public = $this->handler->shouldAllowPublic() &&
                PhabricatorEnv::getEnvConfig('policy.allow-public');
            if (!$allow_public) {
                if (!$user->isLoggedIn() && !$user->isOmnipotent()) {
                    // TODO: As per below, this should get centralized and cleaned up.
                    throw new ConduitException('ERR-INVALID-AUTH');
                }
            }

            // TODO: This would be slightly cleaner by just using a Query, but the
            // Conduit auth workflow requires the Call and User be built separately.
            // Just do it this way for the moment.
            $application = $this->handler->getApplication();
            if ($application) {
                $can_view = PhabricatorPolicyFilter::hasCapability(
                    $user,
                    $application, PhabricatorPolicyCapability::CAN_VIEW);

                if (!$can_view) {
                    throw new ConduitException(
                        \Yii::t("app",
                            'You do not have access to the application which provides this ' .
                            'API method.'));
                }
            }
        }

        return $this->handler->executeMethod($this->request);
    }

    /**
     * @param $method_name
     * @return mixed
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildMethodHandler($method_name)
    {
        $method = ConduitAPIMethod::getConduitMethod($method_name);

        if (!$method) {
            throw new ConduitMethodDoesNotExistException($method_name);
        }

        $application = $method->getApplication();
        if ($application && !$application->isInstalled()) {
            $app_name = $application->getName();
            throw new ConduitApplicationNotInstalledException($method, $app_name);
        }

        return $method;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMethodImplementation()
    {
        return $this->handler;
    }


}
