<?php

namespace orangins\modules\auth\actions;

use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\exception\PhabricatorAuthHighSecurityRequiredException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;

/**
 * Class PhabricatorAuthFinishAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthFinishAction extends PhabricatorAuthAction
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
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPartialSessions()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowLegallyNonCompliantUsers()
    {
        return true;
    }

    /**
     * @return \orangins\lib\view\AphrontDialogView|AphrontResponse
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        // If the user already has a full session, just kick them out of here.
        $has_partial_session = $viewer->hasSession() &&
            $viewer->getSession()->getIsPartial();
        if (!$has_partial_session) {
            return (new AphrontRedirectResponse())->setURI(\Yii::$app->getHomeUrl());
        }

        $engine = new PhabricatorAuthSessionEngine();

        // If this cookie is set, the user is headed into a high security area
        // after login (normally because of a password reset) so if they are
        // able to pass the checkpoint we just want to put their account directly
        // into high security mode, rather than prompt them again for the same
        // set of credentials.
        $jump_into_hisec = $request->getCookie(PhabricatorCookies::COOKIE_HISEC);

        try {
            $token = $engine->requireHighSecuritySession(
                $viewer,
                $request,
                '/logout/',
                $jump_into_hisec);
        } catch (PhabricatorAuthHighSecurityRequiredException $ex) {
            $form = (new PhabricatorAuthSessionEngine())->renderHighSecurityForm(
                $ex->getFactors(),
                $ex->getFactorValidationResults(),
                $viewer,
                $request);

            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Provide Multi-Factor Credentials'))
                ->setShortTitle(\Yii::t("app", 'Multi-Factor Login'))
                ->setWidth(AphrontDialogView::WIDTH_FORM)
                ->addHiddenInput(AphrontRequest::TYPE_HISEC, true)
                ->appendParagraph(
                    \Yii::t("app",
                        'Welcome, %s. To complete the process of logging in, provide your ' .
                        'multi-factor credentials.',
                        phutil_tag('strong', array(), $viewer->getUsername())))
                ->appendChild($form->buildLayoutView())
                ->setSubmitURI($request->getPath())
                ->addCancelButton($ex->getCancelURI())
                ->addSubmitButton(\Yii::t("app", 'Continue'));
        }

        // Upgrade the partial session to a full session.
        $engine->upgradePartialSession($viewer);

        // TODO: It might be nice to add options like "bind this session to my IP"
        // here, even for accounts without multi-factor auth attached to them.

        $next = PhabricatorCookies::getNextURICookie($request);
        $request->clearCookie(PhabricatorCookies::COOKIE_NEXTURI);
        $request->clearCookie(PhabricatorCookies::COOKIE_HISEC);

        if (!PhabricatorEnv::isValidLocalURIForLink($next)) {
            $next = \Yii::$app->getHomeUrl();
        }

        return (new AphrontRedirectResponse())->setURI($next);
    }
}
