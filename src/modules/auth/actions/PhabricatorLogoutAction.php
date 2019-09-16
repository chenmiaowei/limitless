<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthSession;
use yii\helpers\Url;

/**
 * Class PhabricatorLogoutAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorLogoutAction extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return true;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldRequireEmailVerification()
    {
        // Allow unverified users to logout.
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        // Allow disabled users to logout.
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
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        if ($request->isFormPost()) {
            // Destroy the user's session in the database so logout works even if
            // their cookies have some issues. We'll detect cookie issues when they
            // try to login again and tell them to clear any junk.
            $phsid = $request->getCookie(PhabricatorCookies::COOKIE_SESSION);
            if (strlen($phsid)) {
                $session = PhabricatorAuthSession::find()
                    ->setViewer($viewer)
                    ->withSessionKeys(array($phsid))
                    ->executeOne();

                if ($session) {
                    $engine = new PhabricatorAuthSessionEngine();
                    $engine->logoutSession($viewer, $session);
                }
            }
            $request->clearCookie(PhabricatorCookies::COOKIE_SESSION);

            return (new AphrontRedirectResponse())->setURI(Url::to(['/auth/index/loggedout']));
        }

        if ($viewer->getPHID()) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Log Out?'))
                ->appendChild(\Yii::t("app", 'Are you sure you want to log out?'))
                ->addSubmitButton(\Yii::t("app", 'Log Out'))
                ->addCancelButton('/');
        }

        return (new AphrontRedirectResponse())->setURI(\Yii::$app->getHomeUrl());
    }

}
