<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasher;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasherUnavailableException;
use orangins\modules\auth\actions\PhabricatorAuthLinkAction;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\actions\PhabricatorAuthStartAction;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\file\models\PhabricatorFile;
use PhutilEmptyAuthAdapter;
use PhutilOpaqueEnvelope;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormRecaptchaControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserLog;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorPasswordAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorPasswordAuthProvider extends PhabricatorAuthProvider
{

    /**
     * @var
     */
    private $adapter;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", 'Username/Password');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getConfigurationHelp()
    {
        return \Yii::t("app",
            "(WARNING) Examine the table below for information on how password " .
            "hashes will be stored in the database.\n\n" .
            "(NOTE) You can select a minimum password length by setting " .
            "`{0}` in configuration.", [
                'account.minimum-password-length'
            ]);
    }

    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    public function renderConfigurationFooter()
    {
        /** @var PhabricatorPasswordHasher[] $hashers */
        $hashers = PhabricatorPasswordHasher::getAllHashers();
        $hashers = OranginsUtil::msort($hashers, 'getStrength');
        $hashers = array_reverse($hashers);


        $yes = JavelinHtml::phutil_tag(
            'strong',
            array(
                'style' => 'color: #009900',
            ),
            \Yii::t("app", 'Yes'));

        $no = JavelinHtml::phutil_tag(
            'strong',
            array(
                'style' => 'color: #990000',
            ),
            \Yii::t("app", 'Not Installed'));

        $best_hasher_name = null;
        try {
            $best_hasher = PhabricatorPasswordHasher::getBestHasher();
            $best_hasher_name = $best_hasher->getHashName();
        } catch (PhabricatorPasswordHasherUnavailableException $ex) {
            // There are no suitable hashers. The user might be able to enable some,
            // so we don't want to fatal here. We'll fatal when users try to actually
            // use this stuff if it isn't fixed before then. Until then, we just
            // don't highlight a row. In practice, at least one hasher should always
            // be available.
        }

        $rows = array();
        $rowc = array();
        foreach ($hashers as $hasher) {
            $is_installed = $hasher->canHashPasswords();

            $rows[] = array(
                $hasher->getHumanReadableName(),
                $hasher->getHashName(),
                $hasher->getHumanReadableStrength(),
                ($is_installed ? $yes : $no),
                ($is_installed ? null : $hasher->getInstallInstructions()),
            );
            $rowc[] = ($best_hasher_name == $hasher->getHashName())
                ? 'highlighted'
                : null;
        }

        $table = new AphrontTableView($rows);
        $table->setRowClasses($rowc);
        $table->setHeaders(
            array(
                \Yii::t("app", 'Algorithm'),
                \Yii::t("app", 'Name'),
                \Yii::t("app", 'Strength'),
                \Yii::t("app", 'Installed'),
                \Yii::t("app", 'Install Instructions'),
            ));

        $table->setColumnClasses(
            array(
                '',
                '',
                '',
                '',
                'wide',
            ));

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Password Hash Algorithms'))
            ->setSubheader(
                \Yii::t("app",
                    'Stronger algorithms are listed first. The highlighted algorithm ' .
                    'will be used when storing new hashes. Older hashes will be ' .
                    'upgraded to the best algorithm over time.'));

        return (new PHUIObjectBoxView())
            ->addBodyClass("p-0")
            ->setHeader($header)
            ->setTable($table);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDescriptionForCreate()
    {
        return \Yii::t("app",
            'Allow users to log in or register using a username and password.');
    }

    /**
     * @return PhutilEmptyAuthAdapter|mixed
     * @author 陈妙威
     */
    public function getAdapter()
    {
        if (!$this->adapter) {
            $adapter = new PhutilEmptyAuthAdapter();
            $adapter->setAdapterType('password');
            $adapter->setAdapterDomain('self');
            $this->adapter = $adapter;
        }
        return $this->adapter;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLoginOrder()
    {
        // Make sure username/password appears first if it is enabled.
        return '100-' . $this->getProviderName();
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldAllowAccountLink()
    {
        return false;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldAllowAccountUnlink()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDefaultRegistrationProvider()
    {
        return true;
    }

    /**
     * @param PhabricatorAuthStartAction $controller
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildLoginForm(
        PhabricatorAuthStartAction $controller)
    {
        $request = $controller->getRequest();
        return $this->renderPasswordLoginForm($request);
    }

    /**
     * @param PhabricatorAuthStartAction $controller
     * @author 陈妙威
     * @return
     * @throws Exception
     */
    public function buildInviteForm(
        PhabricatorAuthStartAction $controller)
    {
        $request = $controller->getRequest();
        $viewer = $request->getViewer();

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->addHiddenInput('invite', true)
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Username'))
                    ->setName('username'));

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle(\Yii::t("app", 'Register an Account'))
            ->appendForm($form)
            ->setSubmitURI('/auth/index/register')
            ->addSubmitButton(\Yii::t("app", 'Continue'));

        return $dialog;
    }

    /**
     * @param PhabricatorAuthLinkAction $controller
     * @author 陈妙威
     * @throws Exception
     */
    public function buildLinkForm(
        PhabricatorAuthLinkAction $controller)
    {
        throw new Exception(\Yii::t("app", "Password providers can't be linked."));
    }

    /**
     * @param AphrontRequest $request
     * @param bool $require_captcha
     * @param bool $captcha_valid
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    private function renderPasswordLoginForm(
        AphrontRequest $request,
        $require_captcha = false,
        $captcha_valid = false)
    {

        $viewer = $request->getViewer();

//        $dialog = (new AphrontDialogView())
//            ->setSubmitURI($this->getLoginURI())
//            ->setUser($viewer)
//            ->setTitle(\Yii::t("app", 'Log In'))
//            ->addSubmitButton(\Yii::t("app", 'Log In'));
//
//        if ($this->shouldAllowRegistration()) {
//            $dialog->addCancelButton(
//                Url::to(['/auth/index/register']),
//                \Yii::t("app", 'Register New Account'));
//        }
//
//        $dialog->addFooter(
//            JavelinHtml::phutil_tag(
//                'a',
//                array(
//                    'href' => Url::to(['/auth/login/email']),
//                ),
//                \Yii::t("app", 'Forgot your password?')));
//
//        $v_user = OranginsUtil::nonempty(
//            $request->getStr('username'),
//            $request->getCookie(PhabricatorCookies::COOKIE_USERNAME));
//
//        $e_user = null;
//        $e_pass = null;
//        $e_captcha = null;
//
//        $errors = array();
//        if ($require_captcha && !$captcha_valid) {
//            if (AphrontFormRecaptchaControl::hasCaptchaResponse($request)) {
//                $e_captcha = \Yii::t("app", 'Invalid');
//                $errors[] = \Yii::t("app", 'CAPTCHA was not entered correctly.');
//            } else {
//                $e_captcha = \Yii::t("app", 'Required');
//                $errors[] = \Yii::t("app",
//                    'Too many login failures recently. You must ' .
//                    'submit a CAPTCHA with your login request.');
//            }
//        } else if ($request->isHTTPPost()) {
//            // NOTE: This is intentionally vague so as not to disclose whether a
//            // given username or email is registered.
//            $e_user = \Yii::t("app", 'Invalid');
//            $e_pass = \Yii::t("app", 'Invalid');
//            $errors[] = \Yii::t("app", 'Username or password are incorrect.');
//        }
//
//        if ($errors) {
//            $errors = (new PHUIInfoView())->setErrors($errors);
//        }
//
//        $form = (new PHUIFormLayoutView())
//            ->setFullWidth(true)
//            ->appendChild($errors)
//            ->appendChild(
//                (new AphrontFormTextControl())
//                    ->setLabel(\Yii::t("app", 'Username or Email'))
//                    ->setName('username')
//                    ->setValue($v_user)
//                    ->setError($e_user))
//            ->appendChild(
//                (new AphrontFormPasswordControl())
//                    ->setLabel(\Yii::t("app", 'Password'))
//                    ->setName('password')
//                    ->setError($e_pass));
//
//        if ($require_captcha) {
//            $form->appendChild(
//                (new AphrontFormRecaptchaControl())
//                    ->setError($e_captcha));
//        }
//
//        $dialog->appendChild($form);
//
//        return $dialog;

        $e_user = null;
        $e_pass = null;
        $v_user = OranginsUtil::nonempty(
            $request->getStr('username'),
            $request->getCookie(PhabricatorCookies::COOKIE_USERNAME));

        if ($request->isHTTPPost()) {
            // NOTE: This is intentionally vague so as not to disclose whether a
            // given username or email is registered.
            $e_user = "登录失败，请检查用户名是否正确。";
            $e_pass = "登录失败，请检查密码是否正确。";
        }


        $img = JavelinHtml::phutil_tag('div', array(
            'class' => 'col-lg-5 d-flex justify-content-center align-items-center',
        ), array(
            JavelinHtml::phutil_tag('img', array(
                'class' => 'login-img',
                'src' => PhabricatorFile::loadBuiltin('login.png')->getViewURI(),
                'width' => '212px',
                'height' => '161px',
            ))
        ));

        $form = (new AphrontFormView())
            ->setUser($request->getViewer())
            ->addClass('w-100')
            ->setAction($this->getLoginURI())
            ->setEncType('multipart/form-data')
            ->appendChild(
                JavelinHtml::phutil_tag('div', array(
                    'class' => 'welcome',
                ), array('欢迎登录' . PhabricatorEnv::getEnvConfig("orangins.site-name")))
            )
            ->appendChild(
                JavelinHtml::phutil_tag('input', array(
                    'class' => 'form-control input-username',
                    'placeholder' => '请输入用户名/手机号',
                    'name' => 'username',
                    'value' => $v_user,
                ))
            )
            ->appendChild(JavelinHtml::phutil_tag("p", ['class' => 'text-danger py-1 m-0'], $e_user ? $e_user : new \PhutilSafeHTML("&nbsp;")))
            ->appendChild(
                JavelinHtml::phutil_tag('input', array(
                    'class' => 'form-control input-password',
                    'placeholder' => '请输入密码',
                    'name' => 'password',
                    'type' => 'password'
                ))
            )
            ->appendChild(JavelinHtml::phutil_tag("p", ['class' => 'text-danger py-1 m-0'], $e_pass ? $e_pass : new \PhutilSafeHTML("&nbsp;")))
            ->appendChild(
                JavelinHtml::phutil_tag('button', array(
                    'class' => 'form-control login-btn bg-danger rounded-round mr-3',
                    'type' => 'submit'
                ), '登录')
            );

        $page = JavelinHtml::phutil_tag('div', array(
            'class' => 'py-3 row'
        ), array(
            $img,
            JavelinHtml::phutil_tag('div', array(
                'class' => 'col-md-6 d-inline-flex',
            ), $form
            )));

        return JavelinHtml::phutil_tag("div", [
            "class" => "login-container"
        ], $page);
    }


    /**
     * @param PhabricatorAuthLoginAction $action
     * @return array|mixed
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function processLoginRequest(PhabricatorAuthLoginAction $action)
    {

        $request = $action->getRequest();
        $viewer = $request->getViewer();
        $content_source = PhabricatorContentSource::newFromRequest($request);

        $require_captcha = false;
        $captcha_valid = false;
        if (AphrontFormRecaptchaControl::isRecaptchaEnabled()) {
            $failed_attempts = PhabricatorUserLog::loadRecentEventsFromThisIP(
                PhabricatorUserLog::ACTION_LOGIN_FAILURE,
                60 * 15);
            if (count($failed_attempts) > 5) {
                $require_captcha = true;
                $captcha_valid = AphrontFormRecaptchaControl::processCaptcha($request);
            }
        }

        $response = null;
        $account = null;
        $log_user = null;

        if ($request->isFormPost()) {
            if (!$require_captcha || $captcha_valid) {
                $username_or_email_or_mobile = $request->getStr('username');
                if (strlen($username_or_email_or_mobile)) {
                    $user = PhabricatorUser::find()->andWhere(
                        'username = :username', [
                        ":username" => $username_or_email_or_mobile
                    ])->one();

                    if (!$user) {
                        $user = PhabricatorUser::loadOneWithEmailAddress($username_or_email_or_mobile);
                    }

                    if (!$user) {
                        $user = PhabricatorUser::loadOneWithMobile($username_or_email_or_mobile);
                    }

                    if ($user) {
                        $envelope = new PhutilOpaqueEnvelope($request->getStr('password'));
                        $engine = (new PhabricatorAuthPasswordEngine())
                            ->setViewer($user)
                            ->setContentSource($content_source)
                            ->setPasswordType(PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT)
                            ->setObject($user);

                        if ($engine->isValidPassword($envelope)) {
                            $account = $this->loadOrCreateAccount($user->getPHID());
                            $log_user = $user;
                        }
                    }
                }
            }
        }

        if (!$account) {
            if ($request->isFormPost()) {
                $log = PhabricatorUserLog::initializeNewLog(
                    null,
                    $log_user ? $log_user->getPHID() : null,
                    PhabricatorUserLog::ACTION_LOGIN_FAILURE);
                $log->save();
            }

            $request->clearCookie(PhabricatorCookies::COOKIE_USERNAME);

            $response = $action->buildProviderPageResponse(
                $this,
                $this->renderPasswordLoginForm(
                    $request,
                    $require_captcha,
                    $captcha_valid));
        }

        return array($account, $response);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireRegistrationPassword()
    {
        return true;
    }

    /**
     * @author 陈妙威
     */
    public function getDefaultExternalAccount()
    {
        $adapter = $this->getAdapter();

        return (new PhabricatorExternalAccount())
            ->setAccountType($adapter->getAdapterType())
            ->setAccountDomain($adapter->getAdapterDomain());
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    protected function willSaveAccount(PhabricatorExternalAccount $account)
    {
        parent::willSaveAccount($account);
        $account->setUserPHID($account->getAccountID());
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    public function willRegisterAccount(PhabricatorExternalAccount $account)
    {
        parent::willRegisterAccount($account);
        $account->setAccountID($account->getUserPHID());
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getPasswordProvider()
    {
        $providers = self::getAllEnabledProviders();

        foreach ($providers as $provider) {
            if ($provider instanceof PhabricatorPasswordAuthProvider) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PHUIObjectItemView $item
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    public function willRenderLinkedAccount(
        PhabricatorUser $viewer,
        PHUIObjectItemView $item,
        PhabricatorExternalAccount $account)
    {
        return;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowAccountRefresh()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowEmailTrustConfiguration()
    {
        return false;
    }
}
