<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/13
 * Time: 2:38 PM
 */

namespace orangins\modules\auth\components;


use orangins\lib\request\AphrontRequest;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthSession;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerAccessToken;
use orangins\modules\oauthserver\PhabricatorOAuthServer;
use orangins\modules\people\models\PhabricatorUser;
use Yii;
use Yii\web\Cookie;
use yii\web\User;

/**
 * Class User
 * @package orangins\modules\auth\components
 * @author 陈妙威
 */
class UserComponent extends User
{
    /**
     * @var array
     */
    public $loginUrl = ['/auth/index/start'];

    /**
     * @inheritdoc
     */
    public $identityClass = '\orangins\modules\people\models\PhabricatorUser';

    /**
     * @throws \AphrontQueryException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renewAuthStatus()
    {
        $user = new PhabricatorUser();
        $session_engine = new PhabricatorAuthSessionEngine();
        /** @var AphrontRequest $request */
        $request = Yii::$app->getRequest();
        $phsid = $request->getCookie(PhabricatorCookies::COOKIE_SESSION);
        $access_token = $request->getStr("access_token");
        $access_token = $access_token ? $access_token : $request->getHeaders()->get("Access-Token");

        if ($access_token) {
            $token = PhabricatorOAuthServerAccessToken::find()->andWhere(['token' => $access_token])->one();

            if (!$token) {
                throw new \PhutilAuthCredentialException(\Yii::t("app", 'Access token does not exist.'));
            }

            $oauth_server = new PhabricatorOAuthServer();
            $authorization = $oauth_server->authorizeToken($token);
            if (!$authorization) {
                throw new \PhutilAuthCredentialException(\Yii::t("app", 'Access token is invalid or expired.'));
            }

            $user = PhabricatorUser::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs(array($token->getUserPHID()))
                ->executeOne();
            if (!$user) {
                throw new \PhutilAuthCredentialException(\Yii::t("app", 'Access token is for invalid user.'));
            }
        } else
            if (strlen($phsid)) {
            $session_user = $session_engine->loadUserForSession(
                PhabricatorAuthSession::TYPE_WEB,
                $phsid);
            if ($session_user) {
                $user = $session_user;
            }
        } else {
            // If the client doesn't have a session token, generate an anonymous
            // session. This is used to provide CSRF protection to logged-out users.
            $phsid = $session_engine->establishSession(
                PhabricatorAuthSession::TYPE_WEB,
                null,
                $partial = false);

            // This may be a resource request, in which case we just don't set
            // the cookie.

            /** @var Cookie $cookie */
            $cookie = Yii::createObject([
                'class' => 'yii\web\Cookie',
                'name' => PhabricatorCookies::COOKIE_SESSION,
                'httpOnly' => true,
                'value' => $phsid,
            ]);
            Yii::$app->getResponse()->getCookies()->add($cookie);
        }

        $this->setIdentity($user);
    }
}