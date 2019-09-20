<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/3
 * Time: 6:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\controllers;

use AphrontWriteGuard;
use orangins\modules\auth\actions\PhabricatorAuthFinishAction;
use orangins\modules\auth\actions\PhabricatorAuthNeedsApprovalAction;
use orangins\modules\auth\actions\PhabricatorAuthNeedsMultiFactorAction;
use orangins\modules\auth\actions\PhabricatorAuthStartAction;
use orangins\modules\auth\actions\PhabricatorDisabledUserAction;
use orangins\modules\auth\actions\PhabricatorMustVerifyEmailAction;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront403Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\AphrontResponseProducerInterface;
use orangins\lib\response\AphrontWebpageResponse;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\layout\PHUIApplicationMenuView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUICrumbView;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\people\models\PhabricatorUser;
use Yii;
use Exception;
use yii\base\InvalidRouteException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\web\View;

/**
 * Class Controller
 * @package orangins\lib\controllers
 * @author 陈妙威
 */
class PhabricatorController extends Controller
{
    /**
     * @var AphrontRequest
     */
    private $request;

    /**
     * @var PhabricatorApplication
     */
    public $currentApplication;

    /**
     * @author 陈妙威
     */
    public function init()
    {
        /** @var PhabricatorApplication $currentApplication */
        $currentApplication = $this->module;
        $this->setCurrentApplication($currentApplication);

        $host = Yii::$app->request->getHostInfo();
        PhabricatorEnv::setRequestBaseURI($host);
    }

    /**
     * @return PhabricatorApplication
     */
    public function getCurrentApplication()
    {
        return $this->currentApplication;
    }

    /**
     * @param PhabricatorApplication $currentApplication
     * @return self
     */
    public function setCurrentApplication($currentApplication)
    {
        $this->currentApplication = $currentApplication;
        return $this;
    }

    /**
     * @return AphrontRequest
     * @author 陈妙威
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $request = Yii::$app->request;
            $request->setViewer($this->getViewer());
            $this->request = $request;
        }
        return $this->request;
    }


    /**
     * @return PHUIApplicationMenuView
     * @author 陈妙威
     */
    public function newApplicationMenu()
    {
        return (new PHUIApplicationMenuView())
            ->setViewer($this->getViewer());
    }


    /**
     * @param AphrontRequest $request
     * @return self
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 获取当前用户
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        if (Yii::$app->user->getIsGuest()) {
            return PhabricatorUser::getGuestUser();
        } else {
            /** @var PhabricatorUser $identity */
            $identity = Yii::$app->user->identity;
            return $identity;
        }
    }

    /**
     * @return PhabricatorStandardPageView
     * @author 陈妙威
     */
    public function newPage()
    {
        $phabricatorStandardPageView = new PhabricatorStandardPageView();
        $phabricatorStandardPageView
            ->setRequest($this->getRequest())
            ->setViewer($this->getViewer())
            ->setView($this->getView());
        return $phabricatorStandardPageView;
    }


    /**
     * Renders a view in response to an AJAX request.
     *
     * This method is similar to [[renderPartial()]] except that it will inject into
     * the rendering result with JS/CSS scripts and files which are registered with the view.
     * For this reason, you should use this method instead of [[renderPartial()]] to render
     * a view to respond to an AJAX request.
     *
     * @param $content
     * @return string the rendering result.
     */
    public function renderAjaxContent($content)
    {
        ob_start();
        ob_implicit_flush(false);
        $this->getView()->beginPage();
        $this->getView()->head();
        $this->getView()->beginBody();
        echo $content;
        $this->getView()->endBody();
        $this->getView()->endPage(true);
        return ob_get_clean();
    }


    /**
     * 创建面包屑
     * @return PHUICrumbsView
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function buildApplicationCrumbs()
    {
        $crumbs = array();

        $application = $this->getCurrentApplication();
        if ($application) {
            $icon = $application->getIcon();
            if (!$icon) {
                $icon = 'fa-puzzle';
            }

            $crumbs[] = (new PHUICrumbView())
                ->setHref($application->defaultRoute ? Url::to([$application->defaultRoute]) : $this->getApplicationURI('index/index'))
                ->setName($application->getName())
                ->setIcon($icon);
        }

        $view = new PHUICrumbsView();
        foreach ($crumbs as $crumb) {
            $view->addCrumb($crumb);
        }

        return $view;
    }


    /**
     * @return PHUICrumbsView
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function buildApplicationCrumbsForEditEngine()
    {
        // TODO: This is kind of gross, I'm basically just making this public so
        // I can use it in EditEngine. We could do this without making it public
        // by using controller delegation, or make it properly public.
        return $this->buildApplicationCrumbs();
    }


    /**
     * 获取当前应用的链接
     * @param string $path
     * @return string
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getApplicationURI($path = '')
    {
        if (!$this->getCurrentApplication()) {
            throw new Exception(Yii::t('app', 'No application!'));
        }
        return $this->getCurrentApplication()->getApplicationURI($path);
    }

    /**
     * @param AphrontResponse $response
     * @return AphrontAjaxResponse|AphrontResponse
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function willSendResponse(AphrontResponse $response)
    {
        $request = $this->getRequest();
        if ($response instanceof AphrontDialogResponse) {
            if (!$request->isAjax() && !$request->isQuicksand()) {
                $dialog = $response->getDialog();

                $title = $dialog->getTitle();
                $short = $dialog->getShortTitle();

                $crumbs = $this->buildApplicationCrumbs();
                $crumbs->addTextCrumb(OranginsUtil::coalesce($short, $title));

                $child = $response->buildResponseString();
                $view = (new PhabricatorStandardPageView())
                    ->setRequest($request)
                    ->setDeviceReady(true)
                    ->setTitle($title)
                    ->setCrumbs($crumbs)
                    ->setView($this->getView())
                    ->appendChild($child);

                $content = $view->render();
                $response = (new AphrontWebpageResponse())
                    ->setContent($content)
                    ->setHTTPResponseCode($response->getHTTPResponseCode());
            } else {
                $response->getDialog()->setIsStandalone(true);
                $buildResponseString = $response->buildResponseString();
                return (new AphrontAjaxResponse())
                    ->setContent(array(
                        'dialog' => $buildResponseString,
                    ));
            }
        } else if ($response instanceof Aphront404Response) {
            if (!$request->isAjax() && !$request->isQuicksand()) {
                $view = (new PhabricatorStandardPageView())
                    ->setTitle(\Yii::t("app", '404 Not Found'))
                    ->setRequest($request)
                    ->setDeviceReady(true)
                    ->appendChild($response->buildResponseString());

                $content = $view->render();
                $response = (new AphrontWebpageResponse())
                    ->setContent($content)
                    ->setHTTPResponseCode($response->getHTTPResponseCode());
            } else {
                $buildResponseString = $response->buildResponseString();
                return (new AphrontAjaxResponse())
                    ->setContent(array(
                        'dialog' => $buildResponseString,
                    ));
            }
        } else if ($response instanceof AphrontRedirectResponse) {
            if ($request->isAjax() || $request->isQuicksand()) {
                return (new AphrontAjaxResponse())
                    ->setContent(
                        array(
                            'redirect' => $response->getURI(),
                            'close' => $response->getCloseDialogBeforeRedirect(),
                        ));
            }
        }

        return $response;
    }


    /**
     * Write an entire @{class:AphrontResponse} to the output.
     *
     * @param AphrontResponse $response
     * @return string
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     */
    final public function writeResponse(AphrontResponse $response)
    {
        $response->willBeginWrite();

        // Build the content iterator first, in case it throws. Ideally, we'd
        // prefer to handle exceptions before we emit the response status or any
        // HTTP headers.
        $data = $response->getContentIterator();

        $all_headers = array_merge(
            $response->getHeaders(),
            $response->getCacheHeaders());

        foreach ($all_headers as $all_header) {
            if ($all_header[0] === "Content-Type" && strpos($all_header[1], "html") === false) {
                Yii::$app->response->format = Response::FORMAT_RAW;
            }
            Yii::$app->response->headers->set($all_header[0], $all_header[1]);
        }
        Yii::$app->response->setStatusCode($response->getHTTPResponseCode());

        // Allow clients an unlimited amount of time to download the response.

        // This allows clients to perform a "slow loris" attack, where they
        // download a large response very slowly to tie up process slots. However,
        // concurrent connection limits and "RequestReadTimeout" already prevent
        // this attack. We could add our own minimum download rate here if we want
        // to make this easier to configure eventually.

        // For normal page responses, we've fully rendered the page into a string
        // already so all that's left is writing it to the client.

        // For unusual responses (like large file downloads) we may still be doing
        // some meaningful work, but in theory that work is intrinsic to streaming
        // the response.

        set_time_limit(0);

        $result = [];
        $abort = false;
        foreach ($data as $block) {
            if (!$this->isWritable()) {
                $abort = true;
                break;
            }
            $result[] = $block;
        }

        $response->didCompleteWrite($abort);

        return implode("\n", $result);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function isWritable()
    {
        return true;
    }

    /**
     * Resolves a response object into an @{class:AphrontResponse}.
     *
     * Controllers are permitted to return actual responses of class
     * @{class:AphrontResponse}, or other objects which implement
     * @{interface:AphrontResponseProducerInterface} and can produce a response.
     *
     * If a controller returns a response producer, invoke it now and produce
     * the real response.
     *
     * @param AphrontRequest $request
     * @param AphrontRequest Request being handled.
     * @return AphrontResponse Response after any required production.
     * @throws Exception
     * @task response
     */
    private function produceResponse(AphrontRequest $request, $response)
    {
        $original = $response;

        // Detect cycles on the exact same objects. It's still possible to produce
        // infinite responses as long as they're all unique, but we can only
        // reasonably detect cycles, not guarantee that response production halts.

        $seen = array();
        while (true) {
            // NOTE: It is permissible for an object to be both a response and a
            // response producer. If so, being a producer is "stronger". This is
            // used by AphrontProxyResponse.

            // If this response is a valid response, hand over the request first.
            if ($response instanceof AphrontResponse) {
                $response->setRequest($request);
            }

            // If this isn't a producer, we're all done.
            if (!($response instanceof AphrontResponseProducerInterface)) {
                break;
            }

            $hash = spl_object_hash($response);
            if (isset($seen[$hash])) {
                throw new Exception(
                    Yii::t('app',
                        'Failure while producing response for object of class "{0}": ' .
                        'encountered production cycle (identical object, of class "{1}", ' .
                        'was produced twice).',
                        [
                            get_class($original),
                            get_class($response)
                        ]));
            }

            $seen[$hash] = true;

            $new_response = $response->produceAphrontResponse();
            $this->validateProducerResponse($response, $new_response);
            $response = $new_response;
        }

        return $response;
    }

    /**
     * Verifies that the return value from an
     * @{class:AphrontResponseProducerInterface} is of an allowed type.
     *
     * @param AphrontResponseProducerInterface $producer
     * @param AphrontResponseProducerInterface Object which produced
     *   this response.
     * @return void
     * @throws Exception
     * @task response
     */
    private function validateProducerResponse(
        AphrontResponseProducerInterface $producer,
        $response)
    {

        if ($this->isValidResponseObject($response)) {
            return;
        }

        throw new Exception(
            Yii::t('app', 'Producer "{0}" returned an invalid response from call to "{1}". ' .
                'This method must return an object of class "{2}", or an object ' .
                'which implements the "{3}" interface.',
                [
                    get_class($producer),
                    'produceAphrontResponse()',
                    'AphrontResponse',
                    'AphrontResponseProducerInterface'
                ]));
    }

    /**
     * Tests if a response is of a valid type.
     *
     * @param array Supposedly valid response.
     * @return bool True if the object is of a valid type.
     * @task response
     */
    private function isValidResponseObject($response)
    {
        if ($response instanceof AphrontResponse) {
            return true;
        }

        if ($response instanceof AphrontResponseProducerInterface) {
            return true;
        }

        return false;
    }

    /**
     * @param string $id
     * @param array $params
     * @return Aphront403Response|mixed
     * @throws InvalidRouteException
     * @throws Exception
     * @author 陈妙威
     */
    public function runAction($id, $params = [])
    {
        /** @var PhabricatorAction $action */
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }
        $write_guard = new AphrontWriteGuard(array($this, 'validateRequest'));
        $processing_exception = null;
        try {
            $response = $this->willBeginExecution($action);

            if (!$response) {
                $response = parent::runAction($id, $params);
            }
        } catch (\Exception $e) {
            $processing_exception = $e;
        }
        $write_guard->dispose();
        if ($processing_exception) {
            throw $processing_exception;
        } else {
            return $response;
        }
    }

    /**
     * @param $action
     * @return string
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function willBeginExecution($action)
    {
        if ($action instanceof PhabricatorAction) {
            $request = $this->getRequest();
            $user = $this->getViewer();
            // NOTE: We want to set up the user first so we can render a real page
            // here, but fire this before any real logic.
            $restricted = array(
                'code',
            );
            foreach ($restricted as $parameter) {
                if ($request->getExists($parameter)) {
                    if (!$action->shouldAllowRestrictedParameter($parameter)) {
                        throw new Exception(
                            \Yii::t("app",
                                'Request includes restricted parameter "{0}", but this ' .
                                'controller ("{1}") does not whitelist it. Refusing to ' .
                                'serve this request because it might be part of a redirection ' .
                                'attack.', [
                                    $parameter,
                                    get_class($this)
                                ]));
                    }
                }
            }

            if ($action->shouldRequireEnabledUser()) {
                if ($user->getIsDisabled()) {
                    $action = new PhabricatorDisabledUserAction('disable-user', $this);
                    return $this->processActionResponse($action->delegateToAction($action));
                }
            }

            $auth_class = PhabricatorAuthApplication::class;
            $auth_application = PhabricatorApplication::getByClass($auth_class);

            // Require partial sessions to finish login before doing anything.
            if (!$action->shouldAllowPartialSessions()) {
                if ($user->hasSession() &&
                    $user->getSession()->getIsPartial()) {
                    $action = new PhabricatorAuthFinishAction('auth-finish', $this);
                    $this->setCurrentApplication($auth_application);
                    return $this->processActionResponse($action->delegateToAction($action));
                }
            }

            // Require users sign Legalpad documents before we check if they have
            // MFA. If we don't do this, they can get stuck in a state where they
            // can't add MFA until they sign, and can't sign until they add MFA.
            // See T13024 and PHI223.
//            $result = $action->requireLegalpadSignatures();
//            if ($result !== null) {
//                return $this->processActionResponse($result);
//            }

            // Check if the user needs to configure MFA.
            $need_mfa = $action->shouldRequireMultiFactorEnrollment();
            $have_mfa = $user->getIsEnrolledInMultiFactor();
            if ($need_mfa && !$have_mfa) {
                // Check if the cache is just out of date. Otherwise, roadblock the user
                // and require MFA enrollment.
                $user->updateMultiFactorEnrollment();
                if (!$user->getIsEnrolledInMultiFactor()) {
                    $mfa_controller = new PhabricatorAuthNeedsMultiFactorAction('auth-needs-multi-factor', $this);
                    $this->setCurrentApplication($auth_application);
                    return $this->processActionResponse($action->delegateToAction($mfa_controller));
                }
            }

            if ($action->shouldRequireLogin()) {
                // This actually means we need either:
                //   - a valid user, or a public controller; and
                //   - permission to see the application; and
                //   - permission to see at least one Space if spaces are configured.

                $allow_public = $action->shouldAllowPublic() &&
                    PhabricatorEnv::getEnvConfig('policy.allow-public');

                // If this controller isn't public, and the user isn't logged in, require
                // login.
                if (!$allow_public && !$user->isLoggedIn()) {
                    $action = new PhabricatorAuthStartAction('auth-start', $this);
                    $this->setCurrentApplication($auth_application);
                    return $this->processActionResponse($action->delegateToAction($action));
                }

                if ($user->isLoggedIn()) {
                    if ($action->shouldRequireEmailVerification()) {
                        if (!$user->getIsEmailVerified()) {
                            $action = new PhabricatorMustVerifyEmailAction('must-verify-email', $this);
                            $this->setCurrentApplication($auth_application);
                            return $this->processActionResponse($action->delegateToAction($action));
                        }
                    }
                }

                // If Spaces are configured, require that the user have access to at
                // least one. If we don't do this, they'll get confusing error messages
                // later on.
//                $spaces = PhabricatorSpacesNamespaceQuery::getSpacesExist();
//                if ($spaces) {
//                    $viewer_spaces = PhabricatorSpacesNamespaceQuery::getViewerSpaces(
//                        $user);
//                    if (!$viewer_spaces) {
//                        $action = new PhabricatorSpacesNoAccessController();
//                        return $this->processActionResponse($action->delegateToAction($action));
//                    }
//                }

                // If the user doesn't have access to the application, don't let them use
                // any of its controllers. We query the application in order to generate
                // a policy exception if the viewer doesn't have permission.
                $application = $this->getCurrentApplication();
                if ($application) {
                    (new PhabricatorApplicationQuery())
                        ->setViewer($user)
                        ->withPHIDs(array($application->getPHID()))
                        ->executeOne();
                }

                // If users need approval, require they wait here. We do this near the
                // end so they can take other actions (like verifying email, signing
                // documents, and enrolling in MFA) while waiting for an admin to take a
                // look at things. See T13024 for more discussion.
                if ($action->shouldRequireEnabledUser()) {
                    if ($user->isLoggedIn() && !$user->getIsApproved()) {
                        $action = new PhabricatorAuthNeedsApprovalAction('auth-needs-approval', $this);
                        return $this->processActionResponse($action->delegateToAction($action));
                    }
                }
            }

            // NOTE: We do this last so that users get a login page instead of a 403
            // if they need to login.
            if ($action->shouldRequireAdmin() && !$user->getIsAdmin()) {
                return $this->processActionResponse(new Aphront403Response());
            }
        }
    }

    /**
     * @author 陈妙威
     */
    public function validateRequest()
    {

    }


    /**
     * @param PhabricatorAction $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     * @author 陈妙威
     */
    public function beforeAction($action)
    {
        $view = $this->getView();
        $this->getView()->on(View::EVENT_END_BODY, function () use ($view) {
            $celerityStaticResourceResponse = CelerityAPI::getStaticResourceResponse();
            echo $celerityStaticResourceResponse->renderHTMLFooter($view, null);
        });

        if ($action->hasProperty('enableCsrfValidation')) {
            $this->enableCsrfValidation = $action->enableCsrfValidation;
        }
        return parent::beforeAction($action);
    }


    /**
     * @param \yii\base\Action $action
     * @param $response
     * @return string
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     */
    public function afterAction($action, $response)
    {
        $response = parent::afterAction($action, $response);
        return $this->processActionResponse($response);
    }


    /**
     * @param $response
     * @return string
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function processActionResponse($response)
    {
        $response = $this->produceResponse($this->getRequest(), $response);
        $response = $this->willSendResponse($response);
        if ($response instanceof AphrontRedirectResponse) {
            return Yii::$app->response->redirect($response->getURI());
        } else {
            $response->setRequest($this->getRequest());
            $response = $this->writeResponse($response);
            return $response;
        }
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isGlobalDragAndDropUploadEnabled()
    {
        return false;
    }
}