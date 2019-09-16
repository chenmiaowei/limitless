<?php

namespace orangins\modules\auth\provider;

use orangins\lib\request\AphrontRequest;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\adapter\PhabricatorMobileAuthAdapter;
use PhutilAuthAdapter;
use Exception;

/**
 * Class PhabricatorWordPressAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorMobileAuthProvider extends PhabricatorAuthProvider
{

    /**
     * @var PhutilAuthAdapter
     */
    public $adapter;


    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", '手机登录');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'mobile';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function autoRegister()
    {
        return true;
    }


    /**
     * @param PhabricatorAuthLoginAction $action
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \Throwable
     * @author 陈妙威
     */
    public function processLoginRequest(PhabricatorAuthLoginAction $action)
    {
        $request = $action->getRequest();
        $adapter = $this->getAdapter();

        $account = null;
        $response = null;

        $mobile = $request->getStr("mobile");
        $adapter->setMobile($mobile);

        // NOTE: As a side effect, this will cause the OAuth adapter to request
        // an access token.

        try {
            $account_id = $adapter->getAccountID();
        } catch (Exception $ex) {
            throw $ex;
        }

        if (!strlen($account_id)) {
            $response = $action->buildProviderErrorResponse(
                $this,
                \Yii::t("app",
                    'The OAuth provider failed to retrieve an account ID.'));

            return array($account, $response);
        }
        return array($this->loadOrCreateAccount($account_id), $response);
    }


    /**
     * @param AphrontRequest $request
     * @param $mode
     * @return array
     * @author 陈妙威
     */
    protected function renderLoginForm(AphrontRequest $request, $mode)
    {
        return null;
    }


    /**
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhabricatorMobileAuthAdapter();
    }

    /**
     * @return PhabricatorMobileAuthAdapter
     * @author 陈妙威
     */
    public function getAdapter()
    {

        if (!$this->adapter) {
            $adapter = $this->newOAuthAdapter();
            $this->adapter = $adapter;
        }
        return $this->adapter;
    }
}
