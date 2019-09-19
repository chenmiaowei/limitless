<?php

namespace orangins\modules\conduit\actions;

use AphrontWriteGuard;
use ConduitClient;
use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\log\PhabricatorAccessLog;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontJSONResponse;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthSession;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\conduit\call\ConduitCall;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\models\PhabricatorConduitMethodCallLog;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\ConduitAPIResponse;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\conduit\protocol\exception\ConduitMethodNotFoundException;
use orangins\modules\oauthserver\models\PhabricatorOAuthClientAuthorization;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerAccessToken;
use orangins\modules\oauthserver\PhabricatorOAuthServer;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\auth\sshkey\PhabricatorAuthSSHPublicKey;
use orangins\modules\userservice\conduitprice\UserServiceConduitPriceCounter;
use orangins\modules\userservice\exceptions\UserServiceNotSufficientFundsException;
use PhutilJSON;
use PhutilJSONParserException;
use PhutilProxyException;
use PhutilUTF8StringTruncator;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConduitAPIController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitAPIController
    extends PhabricatorConduitController
{

    public $enableCsrfValidation = false;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return AphrontJSONResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $method = $request->getURIData('method');
        $time_start = microtime(true);

        $api_request = null;
        $method_implementation = null;

        $log = new PhabricatorConduitMethodCallLog();
        $log->setMethod($method);
//        $metadata = array();
//        $multimeter = MultimeterControl::getInstance();
//        if ($multimeter) {
//            $multimeter->setEventContext('api.' . $method);
//        }

        try {

            list($metadata, $params, $strictly_typed) = $this->decodeConduitParams(
                $request,
                $method);

            $call = new ConduitCall($method, $params, $strictly_typed);
            $method_implementation = $call->getMethodImplementation();

            $result = null;

            // TODO: The relationship between ConduitAPIRequest and ConduitCall is a
            // little odd here and could probably be improved. Specifically, the
            // APIRequest is a sub-object of the Call, which does not parallel the
            // role of AphrontRequest (which is an indepenent object).
            // In particular, the setUser() and getUser() existing independently on
            // the Call and APIRequest is very awkward.

            $api_request = $call->getAPIRequest();

            $allow_unguarded_writes = false;
            $auth_error = null;
            $conduit_username = '-';
            if ($call->shouldRequireAuthentication()) {
                $auth_error = $this->authenticateUser($api_request, $metadata, $method);
                // If we've explicitly authenticated the user here and either done
                // CSRF validation or are using a non-web authentication mechanism.
                $allow_unguarded_writes = true;

                if ($auth_error === null) {
                    $conduit_user = $api_request->getUser();
                    if ($conduit_user && $conduit_user->getPHID()) {
                        $conduit_username = $conduit_user->getUsername();
                    }
                    $call->setUser($api_request->getUser());
                }
            }

            $access_log = PhabricatorAccessLog::getLog();
            if ($access_log) {
                $access_log->setData(
                    array(
                        'r' => \Yii::$app->request->getUerHostIP(),
                        'a' => ArrayHelper::getValue($metadata, 'token'),
                        'u' => $conduit_username,
                        'm' => $method,
                    ));
            }

            if ($call->shouldPayFee() && $api_request->getUser() && $api_request->getUser()->isLoggedIn()) {
                $userServiceConduitPriceCounter = new UserServiceConduitPriceCounter($api_request->getUser(), $call->getHandler());
                $userServiceConduitPriceCounter->recharge();
            }

            if ($call->shouldAllowUnguardedWrites()) {
                $allow_unguarded_writes = true;
            }

            if ($auth_error === null) {


                if ($allow_unguarded_writes) {
                    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                }

                try {
                    $result = $call->execute();
                    $error_code = null;
                    $error_info = null;
                } catch (ConduitException $ex) {
                    $result = null;
                    $error_code = $ex->getMessage();
                    if ($ex->getErrorDescription()) {
                        $error_info = $ex->getErrorDescription();
                    } else {
                        $error_info = $call->getErrorDescription($error_code);
                    }
                }
                if ($allow_unguarded_writes) {
                    unset($unguarded);
                }
            } else {
                list($error_code, $error_info) = $auth_error;
            }
        } catch (Exception $ex) {
            if (!($ex instanceof ConduitMethodNotFoundException)) {
                \Yii::error($ex);
            }
            $result = null;
            $error_code = 'ERR-CONDUIT-CORE';
            if ($ex instanceof ConduitException) {
                $error_code = "ERR-CONDUIT-CALL";
            } else if ($ex instanceof UserServiceNotSufficientFundsException) {
                $error_code = "ERR-CONDUIT-NOT-SUFFICIENT-FUND";
            }
            $error_info = $ex->getMessage();
        }

        $log
            ->setCallerPHID(
                isset($conduit_user)
                    ? $conduit_user->getPHID()
                    : null)
            ->setError((string)$error_code)
            ->setDuration(phutil_microseconds_since($time_start));


        if (!PhabricatorEnv::isReadOnly()) {
            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            $log->save();
            unset($unguarded);
        }

        $response = (new ConduitAPIResponse())
            ->setResult($result)
            ->setErrorCode($error_code)
            ->setErrorInfo($error_info);

        switch ($request->getStr('output')) {
            case 'human':
                return $this->buildHumanReadableResponse(
                    $method,
                    $api_request,
                    $response->toDictionary(),
                    $method_implementation);
            case 'json':
            default:
                return (new AphrontJSONResponse())
                    ->setAddJSONShield(false)
                    ->setContent($response->toDictionary());
//                return (new AphrontJSONResponse())
//                    ->setAddJSONShield(false)
//                    ->setContent([]);

        }
    }

    /**
     * Authenticate the client making the request to a Phabricator user account.
     *
     * @param ConduitAPIRequest $api_request
     * @param array $metadata
     * @param   ConduitAPIRequest Request being executed.
     * @return array|null
     *                            an error code and error message pair.
     * @throws PhutilProxyException
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     */
    private function authenticateUser(
        ConduitAPIRequest $api_request,
        array $metadata,
        $method)
    {

        $request = $this->getRequest();

        if ($request->getViewer()->getPHID()) {
            return $this->validateAuthenticatedUser(
                $api_request,
                $request->getViewer());
        }

        $auth_type = ArrayHelper::getValue($metadata, 'auth.type');
        if ($auth_type === ConduitClient::AUTH_ASYMMETRIC) {
            $host = ArrayHelper::getValue($metadata, 'auth.host');
            if (!$host) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app",
                        'Request is missing required "{0}" parameter.', [
                            'auth.host'
                        ]),
                );
            }

            // TODO: Validate that we are the host!

            $raw_key = ArrayHelper::getValue($metadata, 'auth.key');
            $public_key = PhabricatorAuthSSHPublicKey::newFromRawKey($raw_key);
            $ssl_public_key = $public_key->toPKCS8();

            // First, verify the signature.
            try {
                $protocol_data = $metadata;
                ConduitClient::verifySignature(
                    $method,
                    $api_request->getAllParameters(),
                    $protocol_data,
                    $ssl_public_key);
            } catch (Exception $ex) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app",
                        'Signature verification failure. %s',
                        $ex->getMessage()),
                );
            }

            // If the signature is valid, find the user or device which is
            // associated with this public key.

            $stored_key = PhabricatorAuthSSHKey::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withKeys(array($public_key))
                ->withIsActive(true)
                ->executeOne();
            if (!$stored_key) {
                $key_summary = (new PhutilUTF8StringTruncator())
                    ->setMaximumBytes(64)
                    ->truncateString($raw_key);
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app",
                        'No user or device is associated with the public key "%s".',
                        $key_summary),
                );
            }

            $object = $stored_key->getObject();

            if ($object instanceof PhabricatorUser) {
                $user = $object;
            } else {
                if (!$stored_key->getIsTrusted()) {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'The key which signed this request is not trusted. Only ' .
                            'trusted keys can be used to sign API calls.'),
                    );
                }

                if (!PhabricatorEnv::isClusterRemoteAddress()) {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'This request originates from outside of the Phabricator ' .
                            'cluster address range. Requests signed with trusted ' .
                            'device keys must originate from within the cluster.'),
                    );
                }

                $user = PhabricatorUser::getOmnipotentUser();

                // Flag this as an intracluster request.
                $api_request->setIsClusterRequest(true);
            }

            return $this->validateAuthenticatedUser(
                $api_request,
                $user);
        } else if ($auth_type === null) {
            // No specified authentication type, continue with other authentication
            // methods below.
        } else {
            return array(
                'ERR-INVALID-AUTH',
                \Yii::t("app",
                    'Provided "%s" ("%s") is not recognized.',
                    'auth.type',
                    $auth_type),
            );
        }

        $token_string = ArrayHelper::getValue($metadata, 'token');
        if (strlen($token_string)) {

            if (strlen($token_string) != 32) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app",
                        'API token "{0}" has the wrong length. API tokens should be ' .
                        '32 characters long.', [
                            $token_string
                        ]),
                );
            }

            $type = head(explode('-', $token_string));
            $valid_types = PhabricatorConduitToken::getAllTokenTypes();
            $valid_types = array_fuse($valid_types);
            if (empty($valid_types[$type])) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app",
                        'API token "{0}" has the wrong format. API tokens should be ' .
                        '32 characters long and begin with one of these prefixes: {1}.', [
                            $token_string,
                            implode(', ', $valid_types)
                        ]),
                );
            }

            /** @var PhabricatorConduitToken $token */
            $token = PhabricatorConduitToken::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withTokens(array($token_string))
                ->withExpired(false)
                ->executeOne();
            if (!$token) {
                $token = PhabricatorConduitToken::find()
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withTokens(array($token_string))
                    ->withExpired(true)
                    ->executeOne();
                if ($token) {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'API token "{0}" was previously valid, but has expired.', [
                                $token_string
                            ]),
                    );
                } else {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'API token "{0}" is not valid.', [
                                $token_string
                            ]),
                    );
                }
            }

            $ip = $token->getParameter('ip', []);
            if (!empty($ip)) {
                $remoteIP = \Yii::$app->request->getUerHostIP();
                if (!in_array($remoteIP, $ip)) {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'Current IP "{0}" is not valid.', [
                                $remoteIP
                            ]),
                    );
                }
            }

            // If this is a "cli-" token, it expires shortly after it is generated
            // by default. Once it is actually used, we extend its lifetime and make
            // it permanent. This allows stray tokens to get cleaned up automatically
            // if they aren't being used.
            if ($token->getTokenType() == PhabricatorConduitToken::TYPE_COMMANDLINE) {
                if ($token->getExpires()) {
                    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                    $token->setExpires(null);
                    $token->save();
                    unset($unguarded);
                }
            }

            // If this is a "clr-" token, Phabricator must be configured in cluster
            // mode and the remote address must be a cluster node.
            if ($token->getTokenType() == PhabricatorConduitToken::TYPE_CLUSTER) {
                if (!PhabricatorEnv::isClusterRemoteAddress()) {
                    return array(
                        'ERR-INVALID-AUTH',
                        \Yii::t("app",
                            'This request originates from outside of the Phabricator ' .
                            'cluster address range. Requests signed with cluster API ' .
                            'tokens must originate from within the cluster.'),
                    );
                }

                // Flag this as an intracluster request.
                $api_request->setIsClusterRequest(true);
            }

            $user = $token->getObject();
            if (!($user instanceof PhabricatorUser)) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'API token is not associated with a valid user.'),
                );
            }

            return $this->validateAuthenticatedUser(
                $api_request,
                $user);
        }

        $access_token = ArrayHelper::getValue($metadata, 'access_token');
        if ($access_token) {
            $token = PhabricatorOAuthServerAccessToken::find()->andWhere(['token' => $access_token])->one();

            if (!$token) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'Access token does not exist.'),
                );
            }

            $oauth_server = new PhabricatorOAuthServer();
            $authorization = $oauth_server->authorizeToken($token);
            if (!$authorization) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'Access token is invalid or expired.'),
                );
            }

            $user = PhabricatorUser::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs(array($token->getUserPHID()))
                ->executeOne();
            if (!$user) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'Access token is for invalid user.'),
                );
            }

            $ok = $this->authorizeOAuthMethodAccess($authorization, $method);
            if (!$ok) {
                return array(
                    'ERR-OAUTH-ACCESS',
                    \Yii::t("app", 'You do not have authorization to call this method.'),
                );
            }

            $api_request->setOAuthToken($token);

            return $this->validateAuthenticatedUser(
                $api_request,
                $user);
        }


        // For intracluster requests, use a public user if no authentication
        // information is provided. We could do this safely for any request,
        // but making the API fully public means there's no way to disable badly
        // behaved clients.
        if (PhabricatorEnv::isClusterRemoteAddress()) {
            if (PhabricatorEnv::getEnvConfig('policy.allow-public')) {
                $api_request->setIsClusterRequest(true);

                $user = new PhabricatorUser();
                return $this->validateAuthenticatedUser(
                    $api_request,
                    $user);
            }
        }


        // Handle sessionless auth.
        // TODO: This is super messy.
        // TODO: Remove this in favor of token-based auth.

        if (isset($metadata['authUser'])) {
            $user = PhabricatorUser::find()->andWhere([
                'username' => $metadata['authUser']
            ])->one();
            if (!$user) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'Authentication is invalid.'),
                );
            }
            $token = ArrayHelper::getValue($metadata, 'authToken');
            $signature = ArrayHelper::getValue($metadata, 'authSignature');
            $certificate = $user->getConduitCertificate();
            $hash = sha1($token . $certificate);
            if (!phutil_hashes_are_identical($hash, $signature)) {
                return array(
                    'ERR-INVALID-AUTH',
                    \Yii::t("app", 'Authentication is invalid.'),
                );
            }
            return $this->validateAuthenticatedUser(
                $api_request,
                $user);
        }

        // Handle session-based auth.
        // TODO: Remove this in favor of token-based auth.

        $session_key = ArrayHelper::getValue($metadata, 'sessionKey');
        if (!$session_key) {
            return array(
                'ERR-INVALID-SESSION',
                \Yii::t("app", 'Session key is not present.'),
            );
        }

        $user = (new PhabricatorAuthSessionEngine())
            ->loadUserForSession(PhabricatorAuthSession::TYPE_CONDUIT, $session_key);

        if (!$user) {
            return array(
                'ERR-INVALID-SESSION',
                \Yii::t("app", 'Session key is invalid.'),
            );
        }

        return $this->validateAuthenticatedUser(
            $api_request,
            $user);
    }

    /**
     * @param ConduitAPIRequest $request
     * @param PhabricatorUser $user
     * @return array|null
     * @author 陈妙威
     * @throws Exception
     */
    private function validateAuthenticatedUser(
        ConduitAPIRequest $request,
        PhabricatorUser $user)
    {

        if (!$user->canEstablishAPISessions()) {
            return array(
                'ERR-INVALID-AUTH',
                \Yii::t("app", 'User account is not permitted to use the API.'),
            );
        }

        $request->setUser($user);

        (new PhabricatorAuthSessionEngine())
            ->willServeRequestForUser($user);

        return null;
    }

    /**
     * @param $method
     * @param ConduitAPIRequest|null $request
     * @param null $result
     * @param ConduitAPIMethod|null $method_implementation
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildHumanReadableResponse(
        $method,
        ConduitAPIRequest $request = null,
        $result = null,
        ConduitAPIMethod $method_implementation = null)
    {

        $param_rows = array();
        $param_rows[] = array('Method', $this->renderAPIValue($method));
        if ($request) {
            foreach ($request->getAllParameters() as $key => $value) {
                $param_rows[] = array(
                    $key,
                    $this->renderAPIValue($value),
                );
            }
        }

        $param_table = new AphrontTableView($param_rows);
        $param_table->setColumnClasses(
            array(
                'header',
                'wide',
            ));

        $result_rows = array();
        foreach ($result as $key => $value) {
            $result_rows[] = array(
                $key,
                $this->renderAPIValue($value),
            );
        }

        $result_table = new AphrontTableView($result_rows);
        $result_table->setColumnClasses(
            array(
                'header',
                'wide',
            ));

        $param_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Method Parameters'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setTable($param_table);

        $result_panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Method Result'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setTable($result_table);

        $method_uri = $this->getApplicationURI('method/' . $method . '/');

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($method, $method_uri)
            ->addTextCrumb(\Yii::t("app", 'Call'))
            ->setBorder(true);

        $example_panel = null;
        if ($request && $method_implementation) {
            $params = $request->getAllParameters();
            $example_panel = $this->renderExampleBox(
                $method_implementation,
                $params);
        }

        $title = \Yii::t("app", 'Method Call Result');
        $header = (new PHUIHeaderView())
            ->setHeader($title)
            ->setHeaderIcon('fa-exchange');

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter(array(
                $param_panel,
                $result_panel,
                $example_panel,
            ));

        $title = \Yii::t("app", 'Method Call Result');

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

    /**
     * @param $value
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderAPIValue($value)
    {
        $json = new PhutilJSON();
        if (is_array($value)) {
            $value = $json->encodeFormatted($value);
        }

        $value = phutil_tag(
            'pre',
            array('style' => 'white-space: pre-wrap;'),
            $value);

        return $value;
    }

    /**
     * @param AphrontRequest $request
     * @param $method
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private function decodeConduitParams(
        AphrontRequest $request,
        $method)
    {

        // Look for parameters from the Conduit API Console, which are encoded
        // as HTTP POST parameters in an array, e.g.:
        //
        //   params[name]=value&params[name2]=value2
        //
        // The fields are individually JSON encoded, since we require users to
        // enter JSON so that we avoid type ambiguity.

        $params = $request->getArr('params', null);
        if ($params !== null) {
            foreach ($params as $key => $value) {
                if ($value == '') {
                    // Interpret empty string null (e.g., the user didn't type anything
                    // into the box).
                    $value = 'null';
                }
                $decoded_value = json_decode($value, true);
                if ($decoded_value === null && strtolower($value) != 'null') {
//                    // When json_decode() fails, it returns null. This almost certainly
//                    // indicates that a user was using the web UI and didn't put quotes
//                    // around a string value. We can either do what we think they meant
//                    // (treat it as a string) or fail. For now, err on the side of
//                    // caution and fail. In the future, if we make the Conduit API
//                    // actually do type checking, it might be reasonable to treat it as
//                    // a string if the parameter type is string.
                    throw new Exception(
                        \Yii::t("app",
                            "The value for parameter '{0}' is not valid JSON. All " .
                            "parameters must be encoded as JSON values, including strings " .
                            "(which means you need to surround them in double quotes). " .
                            "Check your syntax. Value was: {1}.", [
                                $key,
                                $value
                            ]));
                } else {
                    $params[$key] = $decoded_value;
                }
            }

            $metadata = ArrayHelper::getValue($params, '__conduit__', array());
            unset($params['__conduit__']);

            return array($metadata, $params, true);
        }

        // Otherwise, look for a single parameter called 'params' which has the
        // entire param dictionary JSON encoded.
        $params_json = $request->getStr('params');
        if (strlen($params_json)) {
            $params = null;
            try {
                $params = phutil_json_decode($params_json);
            } catch (PhutilJSONParserException $ex) {
                throw new PhutilProxyException(
                    \Yii::t("app",
                        "Invalid parameter information was passed to method '%s'.",
                        $method),
                    $ex);
            }

            $metadata = ArrayHelper::getValue($params, '__conduit__', array());
            unset($params['__conduit__']);

            return array($metadata, $params, true);
        }

        // If we do not have `params`, assume this is a simple HTTP request with
        // HTTP key-value pairs.
        $params = array();
        $metadata = array();
        foreach ($request->getPassthroughRequestData() as $key => $value) {
            $meta_key = ConduitAPIMethod::getParameterMetadataKey($key);
            if ($meta_key !== null) {
                $metadata[$meta_key] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        return array($metadata, $params, false);
    }

    /**
     * @param PhabricatorOAuthClientAuthorization $authorization
     * @param $method_name
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    private function authorizeOAuthMethodAccess(
        PhabricatorOAuthClientAuthorization $authorization,
        $method_name)
    {

        $method = ConduitAPIMethod::getConduitMethod($method_name);
        if (!$method) {
            return false;
        }

        $required_scope = $method->getRequiredScope();
        switch ($required_scope) {
            case ConduitAPIMethod::SCOPE_ALWAYS:
                return true;
            case ConduitAPIMethod::SCOPE_NEVER:
                return false;
        }

        $authorization_scope = $authorization->getScope();
        if (!empty($authorization_scope[$required_scope])) {
            return true;
        }

        return false;
    }


}
