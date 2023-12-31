<?php

namespace orangins\modules\oauthserver\actions;

use AphrontWriteGuard;
use Exception;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\PhabricatorOAuthServer;
use orangins\modules\oauthserver\PhabricatorOAuthServerScope;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientQuery;
use orangins\modules\policy\exception\PhabricatorPolicyException;
use PhutilURI;

/**
 * Class PhabricatorOAuthServerAuthController
 * @package orangins\modules\oauthserver\actions
 * @author 陈妙威
 */
final class PhabricatorOAuthServerAuthController
    extends PhabricatorOAuthServerController
{

    /**
     * @return PHUICrumbsView
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        // We're specifically not putting an "OAuth Server" application crumb
        // on the auth pages because it doesn't make sense to send users there.
        return new PHUICrumbsView();
    }

    /**
     * @return AphrontDialogResponse|AphrontDialogView
     * @throws PhabricatorPolicyException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $server = new PhabricatorOAuthServer();
        $client_phid = $request->getStr('client_id');
        $redirect_uri = $request->getStr('redirect_uri');
        $response_type = $request->getStr('response_type');

        // state is an opaque value the client sent us for their own purposes
        // we just need to send it right back to them in the response!
        $state = $request->getStr('state');

        if (!$client_phid) {
            return $this->buildErrorResponse(
                'invalid_request',
                pht('Malformed Request'),
                pht(
                    'Required parameter %s was not present in the request.',
                    phutil_tag('strong', array(), 'client_id')));
        }

        // We require that users must be able to see an OAuth application
        // in order to authorize it. This allows an application's visibility
        // policy to be used to restrict authorized users.
        try {
            $client = PhabricatorOAuthServerClient::find()
                ->setViewer($viewer)
                ->withPHIDs(array($client_phid))
                ->executeOne();
        } catch (PhabricatorPolicyException $ex) {
            $ex->setContext(self::CONTEXT_AUTHORIZE);
            throw $ex;
        }

        $server->setUser($viewer);
        $is_authorized = false;
        $authorization = null;
        $uri = null;
        $name = null;

        // one giant try / catch around all the exciting database stuff so we
        // can return a 'server_error' response if something goes wrong!
        try {
            if (!$client) {
                return $this->buildErrorResponse(
                    'invalid_request',
                    pht('Invalid Client Application'),
                    pht(
                        'Request parameter %s does not specify a valid client application.',
                        phutil_tag('strong', array(), 'client_id')));
            }

            if ($client->getIsDisabled()) {
                return $this->buildErrorResponse(
                    'invalid_request',
                    pht('Application Disabled'),
                    pht(
                        'The %s OAuth application has been disabled.',
                        phutil_tag('strong', array(), 'client_id')));
            }

            $name = $client->getName();
            $server->setClient($client);
            if ($redirect_uri) {
                $client_uri = new PhutilURI($client->getRedirectURI());
                $redirect_uri = new PhutilURI($redirect_uri);
                if (!($server->validateSecondaryRedirectURI($redirect_uri,
                    $client_uri))) {
                    return $this->buildErrorResponse(
                        'invalid_request',
                        pht('Invalid Redirect URI'),
                        pht(
                            'Request parameter %s specifies an invalid redirect URI. ' .
                            'The redirect URI must be a fully-qualified domain with no ' .
                            'fragments, and must have the same domain and at least ' .
                            'the same query parameters as the redirect URI the client ' .
                            'registered.',
                            phutil_tag('strong', array(), 'redirect_uri')));
                }
                $uri = $redirect_uri;
            } else {
                $uri = new PhutilURI($client->getRedirectURI());
            }

            if (empty($response_type)) {
                return $this->buildErrorResponse(
                    'invalid_request',
                    pht('Invalid Response Type'),
                    pht(
                        'Required request parameter %s is missing.',
                        phutil_tag('strong', array(), 'response_type')));
            }

            if ($response_type != 'code') {
                return $this->buildErrorResponse(
                    'unsupported_response_type',
                    pht('Unsupported Response Type'),
                    pht(
                        'Request parameter %s specifies an unsupported response type. ' .
                        'Valid response types are: %s.',
                        phutil_tag('strong', array(), 'response_type'),
                        implode(', ', array('code'))));
            }


            $requested_scope = $request->getStrList('scope');
            $requested_scope = array_fuse($requested_scope);

            $scope = PhabricatorOAuthServerScope::filterScope($requested_scope);

            // NOTE: We're always requiring a confirmation dialog to redirect.
            // Partly this is a general defense against redirect attacks, and
            // partly this shakes off anchors in the URI (which are not shaken
            // by 302'ing).

            $auth_info = $server->userHasAuthorizedClient($scope);
            list($is_authorized, $authorization) = $auth_info;

            if ($request->isFormPost()) {
                if ($authorization) {
                    $authorization->setScope($scope)->save();
                } else {
                    $authorization = $server->authorizeClient($scope);
                }

                $is_authorized = true;
            }
        } catch (Exception $e) {
            return $this->buildErrorResponse(
                'server_error',
                pht('Server Error'),
                pht(
                    'The authorization server encountered an unexpected condition ' .
                    'which prevented it from fulfilling the request.'));
        }

        // When we reach this part of the controller, we can be in two states:
        //
        //   1. The user has not authorized the application yet. We want to
        //      give them an "Authorize this application?" dialog.
        //   2. The user has authorized the application. We want to give them
        //      a "Confirm Login" dialog.

        if ($is_authorized) {

            // The second case is simpler, so handle it first. The user either
            // authorized the application previously, or has just authorized the
            // application. Show them a confirm dialog with a normal link back to
            // the application. This shakes anchors from the URI.

            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            $auth_code = $server->generateAuthorizationCode($uri);
            unset($unguarded);

            $full_uri = $this->addQueryParams(
                $uri,
                array(
                    'code' => $auth_code->getCode(),
                    'scope' => $authorization->getScopeString(),
                    'state' => $state,
                ));

            if ($client->getIsTrusted()) {
                // NOTE: See T13099. We currently emit a "Content-Security-Policy"
                // which includes a narrow "form-action". At the time of writing,
                // Chrome applies "form-action" to redirects following form submission.

                // This can lead to a situation where a user enters the OAuth workflow
                // and is prompted for MFA. When they submit an MFA response, the form
                // can redirect here, and Chrome will block the "Location" redirect.

                // To avoid this, render an interstitial. We only actually need to do
                // this in Chrome (but do it everywhere for consistency) and only need
                // to do it if the request is a redirect after a form submission (but
                // we can't tell if it is or not).

                Javelin::initBehavior(
                    'redirect',
                    array(
                        'uri' => (string)$full_uri,
                    ));

                return $this->newDialog()
                    ->setTitle(pht('Authenticate: %s', $name))
                    ->appendParagraph(
                        pht(
                            'Authorization for "%s" confirmed, redirecting...',
                            phutil_tag('strong', array(), $name)))
                    ->addCancelButton((string)$full_uri, pht('Continue'));
            }

            // TODO: It would be nice to give the user more options here, like
            // reviewing permissions, canceling the authorization, or aborting
            // the workflow.

            $dialog = (new  AphrontDialogView())
                ->setUser($viewer)
                ->setTitle(pht('Authenticate: %s', $name))
                ->appendParagraph(
                    pht(
                        'This application ("%s") is authorized to use your Phabricator ' .
                        'credentials. Continue to complete the authentication workflow.',
                        phutil_tag('strong', array(), $name)))
                ->addCancelButton((string)$full_uri, pht('Continue to Application'));

            return (new  AphrontDialogResponse())->setDialog($dialog);
        }

        // Here, we're confirming authorization for the application.
        if ($authorization) {
            $missing_scope = array_diff_key($scope, $authorization->getScope());
        } else {
            $missing_scope = $scope;
        }

        $form = (new  AphrontFormView())
            ->addHiddenInput('client_id', $client_phid)
            ->addHiddenInput('redirect_uri', $redirect_uri)
            ->addHiddenInput('response_type', $response_type)
            ->addHiddenInput('state', $state)
            ->addHiddenInput('scope', $request->getStr('scope'))
            ->setUser($viewer);

        $cancel_msg = pht('The user declined to authorize this application.');
        $cancel_uri = $this->addQueryParams(
            $uri,
            array(
                'error' => 'access_denied',
                'error_description' => $cancel_msg,
            ));

        $dialog = $this->newDialog()
            ->setShortTitle(pht('Authorize Access'))
            ->setTitle(pht('Authorize "%s"?', $name))
            ->setSubmitURI($request->getRequestURI()->getPath())
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendParagraph(
                pht(
                    'Do you want to authorize the external application "%s" to ' .
                    'access your Phabricator account data, including your primary ' .
                    'email address?',
                    phutil_tag('strong', array(), $name)))
            ->appendForm($form)
            ->addSubmitButton(pht('Authorize Access'))
            ->addCancelButton((string)$cancel_uri, pht('Do Not Authorize'));

        if ($missing_scope) {
            $dialog->appendParagraph(
                pht(
                    'This application has requested these additional permissions. ' .
                    'Authorizing it will grant it the permissions it requests:'));
            foreach ($missing_scope as $scope_key => $ignored) {
                // TODO: Once we introduce more scopes, explain them here.
            }
        }

        $unknown_scope = array_diff_key($requested_scope, $scope);
        if ($unknown_scope) {
            $dialog->appendParagraph(
                pht(
                    'This application also requested additional unrecognized ' .
                    'permissions. These permissions may have existed in an older ' .
                    'version of Phabricator, or may be from a future version of ' .
                    'Phabricator. They will not be granted.'));

            $unknown_form = (new  AphrontFormView())
                ->setViewer($viewer)
                ->appendChild(
                    (new  AphrontFormTextControl())
                        ->setLabel(pht('Unknown Scope'))
                        ->setValue(implode(', ', array_keys($unknown_scope)))
                        ->setDisabled(true));

            $dialog->appendForm($unknown_form);
        }

        return $dialog;
    }


    /**
     * @param $code
     * @param $title
     * @param $message
     * @return AphrontDialogView
     * @throws Exception
     * @author 陈妙威
     */
    private function buildErrorResponse($code, $title, $message)
    {
        $viewer = $this->getRequest()->getViewer();

        return $this->newDialog()
            ->setTitle(pht('OAuth: %s', $title))
            ->appendParagraph($message)
            ->appendParagraph(
                pht('OAuth Error Code: %s', phutil_tag('tt', array(), $code)))
            ->addCancelButton('/', pht('Alas!'));
    }


    /**
     * @param PhutilURI $uri
     * @param array $params
     * @return PhutilURI
     * @author 陈妙威
     */
    private function addQueryParams(PhutilURI $uri, array $params)
    {
        $full_uri = clone $uri;

        foreach ($params as $key => $value) {
            if (strlen($value)) {
                $full_uri->replaceQueryParam($key, $value);
            }
        }

        return $full_uri;
    }

}
