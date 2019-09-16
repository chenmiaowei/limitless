<?php

namespace orangins\modules\auth\actions;

use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormFileControl;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\handler\PhabricatorAuthLoginHandler;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontPlainTextResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\layout\AphrontMultiColumnView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\file\models\PhabricatorFile;
use app\task\assets\AuthStartAsset;
use orangins\modules\widgets\fancybox\FancyboxAsset;

/**
 * Class PhabricatorAuthStartAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthStartAction extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return AphrontDialogView|AphrontResponse|PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();


        if ($viewer->isLoggedIn()) {
            // Kick the user home if they are already logged in.
            return (new AphrontRedirectResponse())->setURI('/');
        }

        if ($request->isAjax()  ) {
            return $this->processAjaxRequest();
        }

        if ($request->isConduit()) {
            return $this->processConduitRequest();
        }

        // If the user gets this far, they aren't logged in, so if they have a
        // user session token we can conclude that it's invalid: if it was valid,
        // they'd have been logged in above and never made it here. Try to clear
        // it and warn the user they may need to nuke their cookies.

        $session_token = $request->getCookie(PhabricatorCookies::COOKIE_SESSION);
        $did_clear = $request->getStr('cleared');

        if (strlen($session_token)) {
            $kind = PhabricatorAuthSessionEngine::getSessionKindFromToken(
                $session_token);
            switch ($kind) {
                case PhabricatorAuthSessionEngine::KIND_ANONYMOUS:
                    // If this is an anonymous session. It's expected that they won't
                    // be logged in, so we can just continue.
                    break;
                default:
                    // The session cookie is invalid, so try to clear it.
                    $request->clearCookie(PhabricatorCookies::COOKIE_USERNAME);
                    $request->clearCookie(PhabricatorCookies::COOKIE_SESSION);

                    // We've previously tried to clear the cookie but we ended up back
                    // here, so it didn't work. Hard fatal instead of trying again.
                    if ($did_clear) {
                        return $this->renderError(
                            \Yii::t("app",
                                'Your login session is invalid, and clearing the session ' .
                                'cookie was unsuccessful. Try clearing your browser cookies.'));
                    }

                    $redirect_uri = $request->getRequestURI();
                    $redirect_uri->setQueryParam('cleared', 1);
                    return (new AphrontRedirectResponse())->setURI($redirect_uri);
            }
        }

//         If we just cleared the session cookie and it worked, clean up after
//         ourselves by redirecting to get rid of the "cleared" parameter. The
//         the workflow will continue normally.
        if ($did_clear) {
            $redirect_uri = $request->getRequestURI();
            $redirect_uri->setQueryParam('cleared', null);
            return (new AphrontRedirectResponse())->setURI($redirect_uri);
        }

        $providers = PhabricatorAuthProvider::getAllEnabledProviders();
        foreach ($providers as $key => $provider) {
            if (!$provider->shouldAllowLogin()) {
                unset($providers[$key]);
            }
        }

        if (!$providers) {
            if ($this->isFirstTimeSetup()) {
                // If this is a fresh install, let the user register their admin
                // account.
                $uri = $this->getApplicationURI('index/register');
                return (new AphrontRedirectResponse())
                    ->setURI($uri);
            }

            return $this->renderError(
                \Yii::t("app",
                    'This Phabricator install is not configured with any enabled ' .
                    'authentication providers which can be used to log in. If you ' .
                    'have accidentally locked yourself out by disabling all providers, ' .
                    'you can use `{0}` to recover access to an administrative account.',
                    [
                        './bin/auth recover <username>'
                    ]));
        }

        $next_uri = $request->getStr('next');
        if (!strlen($next_uri)) {
            if ($this->getDelegatingAction()) {
                // Only set a next URI from the request path if this controller was
                // delegated to, which happens when a user tries to view a page which
                // requires them to login.

                // If this controller handled the request directly, we're on the main
                // login page, and never want to redirect the user back here after they
                // login.
                $next_uri = (string)$this->getRequest()->getRequestURI();
            }
        }

        if (!$request->isFormPost()) {
            if (strlen($next_uri)) {
                PhabricatorCookies::setNextURICookie($request, $next_uri);
            }
            PhabricatorCookies::setClientIDCookie($request);
        }

        $auto_response = $this->tryAutoLogin($providers);
        if ($auto_response) {
            return $auto_response;
        }

        $invite = $this->loadInvite();

        $not_buttons = array();
        $are_buttons = array();
        /** @var PhabricatorAuthProvider[] $providers */
        $providers = OranginsUtil::msort($providers, 'getLoginOrder');
        foreach ($providers as $provider) {
            if ($invite) {
                $form = $provider->buildInviteForm($this);
            } else {
                $form = $provider->buildLoginForm($this);
            }
            if ($provider->isLoginFormAButton()) {
                $are_buttons[] = $form;
            } else {
                $not_buttons[] = $form;
            }
        }

        $out = array();
        $out[] = $not_buttons;
        if ($are_buttons) {
            foreach ($are_buttons as $key => $button) {

                $are_buttons[$key] = JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'phabricator-login-button mmb',
                    ),
                    $button);
            }

            // If we only have one button, add a second pretend button so that we
            // always have two columns. This makes it easier to get the alignments
            // looking reasonable.
            if (count($are_buttons) == 1) {
                $are_buttons[] = null;
            }

            $button_columns = (new AphrontMultiColumnView())
                ->setFluidLayout(true);
            $are_buttons = array_chunk($are_buttons, ceil(count($are_buttons) / 2));
            foreach ($are_buttons as $column) {
                $button_columns->addColumn($column);
            }

            $out[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phabricator-login-buttons',
                ),
                $button_columns);
        }

        $handlers = PhabricatorAuthLoginHandler::getAllHandlers();

        $delegating_controller = $this->getDelegatingAction();

        $header = array();
        foreach ($handlers as $handler) {
            $handler = clone $handler;

            $handler->setRequest($request);

            if ($delegating_controller) {
                $handler->setDelegatingAction($delegating_controller);
            }

            $header[] = $handler->getAuthLoginHeaderContent();
        }

        $invite_message = null;
        if ($invite) {
            $invite_message = $this->renderInviteHeader($invite);
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Login'));
        $crumbs->setBorder(true);

        $title = \Yii::t("app", 'Login');
        $view = array(
            $header,
            $invite_message,
            $out,
        );
        return $this->newPage()
            ->addContentClass("d-flex justify-content-center align-items-center bg-white")
            ->setTitle($title)
//            ->setCrumbs($crumbs)
            ->appendChild($view);
//

    }


    /**
     * @return \orangins\lib\view\AphrontDialogView|AphrontResponse
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function processAjaxRequest()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        // We end up here if the user clicks a workflow link that they need to
        // login to use. We give them a dialog saying "You need to login...".

        if ($request->isDialogFormPost()) {
            return (new AphrontRedirectResponse())->setURI(
                $request->getRequestURI());
        }

        // Often, users end up here by clicking a disabled action link in the UI
        // (for example, they might click "Edit Subtasks" on a Maniphest task
        // page). After they log in we want to send them back to that main object
        // page if we can, since it's confusing to end up on a standalone page with
        // only a dialog (particularly if that dialog is another error,
        // like a policy exception).

        $via_header = AphrontRequest::getViaHeaderName();
        $via_uri = AphrontRequest::getHTTPHeader($via_header);
        if (strlen($via_uri)) {
            PhabricatorCookies::setNextURICookie($request, $via_uri, $force = true);
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Login Required'))
            ->appendParagraph(\Yii::t("app", 'You must log in to take this action.'))
            ->addSubmitButton(\Yii::t("app", 'Log In'))
            ->addCancelButton('/');
    }


    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function processConduitRequest()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        // A common source of errors in Conduit client configuration is getting
        // the request path wrong. The client will end up here, so make some
        // effort to give them a comprehensible error message.

        $request_path = $this->getRequest()->getPath();
        $conduit_path = '/api/<method>';
        $example_path = '/api/conduit.ping';

        $message = \Yii::t("app",
            'ERROR: You are making a Conduit API request to "{0}", but the correct ' .
            'HTTP request path to use in order to access a COnduit method is "{1}" ' .
            '(for example, "{2}"). Check your configuration.',
            [
                $request_path,
                $conduit_path,
                $example_path
            ]);

        return (new AphrontPlainTextResponse())->setContent($message);
    }

    /**
     * @param $message
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderError($message)
    {
        return $this->renderErrorPage(
            \Yii::t("app", 'Authentication Failure'),
            array($message));
    }

    /**
     * @param PhabricatorAuthProvider[] $providers
     * @return null
     * @throws \Exception
     * @author 陈妙威
     */
    private function tryAutoLogin(array $providers)
    {
        $request = $this->getRequest();

        // If the user just logged out, don't immediately log them in again.
        if ($request->getURIData('loggedout')) {
            return null;
        }

        // If we have more than one provider, we can't autologin because we
        // don't know which one the user wants.
        if (count($providers) != 1) {
            return null;
        }

        /** @var PhabricatorAuthProvider $provider */
        $provider = OranginsUtil::head($providers);
        if (!$provider->supportsAutoLogin()) {
            return null;
        }

        $config = $provider->getProviderConfig();
        if (!$config->getShouldAutoLogin()) {
            return null;
        }

        $auto_uri = $provider->getAutoLoginURI($request);

        return (new AphrontRedirectResponse())
            ->setIsExternal(true)
            ->setURI($auto_uri);
    }

}
