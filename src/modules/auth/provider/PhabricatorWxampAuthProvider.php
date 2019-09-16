<?php

namespace orangins\modules\auth\provider;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\auth\actions\PhabricatorAuthLoginAction;
use orangins\modules\auth\adapter\PhabricatorWxampAuthAdapter;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use PhutilAuthAdapter;
use Exception;
use PhutilOpaqueEnvelope;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorWordPressAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
final class PhabricatorWxampAuthProvider extends PhabricatorAuthProvider
{
    /**
     *
     */
    const PROPERTY_NOTE = 'oauth:app:note';

    /**
     *
     */
    const PROPERTY_APP_ID = 'oauth:app:id';
    /**
     *
     */
    const PROPERTY_APP_SECRET = 'oauth:app:secret';
    /**
     * @var PhutilAuthAdapter
     */
    public $adapter;



    /**
     * @return string
     * @author 陈妙威
     */
    protected function getIDKey()
    {
        return self::PROPERTY_APP_ID;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getSecretKey()
    {
        return self::PROPERTY_APP_SECRET;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderName()
    {
        return \Yii::t("app", '微信小程序');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getLoginIcon()
    {
        return 'wechat';
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
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @throws \Throwable
     */
    public function processLoginRequest(PhabricatorAuthLoginAction $action)
    {
        $request = $action->getRequest();
        $adapter = $this->getAdapter();

        $account = null;
        $response = null;

        $code = $request->getStr("code");
        $encryptedData = $request->getStr('encryptedData');
        $iv = $request->getStr('iv');


        $adapter->setCode($code);
        $adapter->setEncryptedData($encryptedData);
        $adapter->setIv($iv);

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
     * @return null|string
     * @author 陈妙威
     * @throws Exception
     */
    public function getConfigurationHelp()
    {
        $help = $this->getProviderConfigurationHelp();

        return $help . "\n\n" .
            \Yii::t("app",
                'Use the **OAuth App Notes** field to record details about which ' .
                'account the external application is registered under.');
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getProviderConfigurationHelp()
    {
        $uri = PhabricatorEnv::getProductionURI('/');
        $callback_uri = PhabricatorEnv::getURI($this->getLoginURI());

        return \Yii::t("app",
            "To configure WordPress.com OAuth, create a new WordPress.com " .
            "Application here:\n\n" .
            "https://developer.wordpress.com/apps/new/." .
            "\n\n" .
            "You should use these settings in your application:" .
            "\n\n" .
            "  - **URL:** Set this to your full domain with protocol. For this " .
            "    Phabricator install, the correct value is: `{0}`\n" .
            "  - **Redirect URL**: Set this to: `{1}`\n" .
            "\n\n" .
            "Once you've created an application, copy the **Client ID** and " .
            "**Client Secret** into the fields above.",
            [
                $uri,
                $callback_uri
            ]);
    }

    /**
     * @param AphrontRequest $request
     * @param $mode
     * @return wild
     * @author 陈妙威
     */
    protected function renderLoginForm(AphrontRequest $request, $mode)
    {
        return null;
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @author 陈妙威
     * @return
     * @throws \Exception
     */
    public function extendEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues)
    {
        return $this->extendOAuthEditForm(
            $request,
            $form,
            $values,
            $issues,
            \Yii::t("app", 'appid'),
            \Yii::t("app", 'secret'));
    }

    /**
     * 从provider数据库读取配置
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function readFormValuesFromProvider()
    {
        $config = $this->getProviderConfig();
        $id = $config->getProperty($this->getIDKey());
        $secret = $config->getProperty($this->getSecretKey());
        $note = $config->getProperty(self::PROPERTY_NOTE);

        return array(
            $this->getIDKey() => $id,
            $this->getSecretKey() => $secret,
            self::PROPERTY_NOTE => $note,
        );
    }

    /**
     * 从用户请求读取配置
     * @param AphrontRequest $request
     * @return array
     * @author 陈妙威
     */
    public function readFormValuesFromRequest(AphrontRequest $request)
    {
        return array(
            $this->getIDKey() => $request->getStr($this->getIDKey()),
            $this->getSecretKey() => $request->getStr($this->getSecretKey()),
            self::PROPERTY_NOTE => $request->getStr(self::PROPERTY_NOTE),
        );
    }

    /**
     * @param PhabricatorAuthProviderConfigTransaction $xaction
     * @return null|string
     * @throws \PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function renderConfigPropertyTransactionTitle(
        PhabricatorAuthProviderConfigTransaction $xaction)
    {

        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();
        $key = $xaction->getMetadataValue(
            PhabricatorAuthProviderConfigTransaction::PROPERTY_KEY);

        switch ($key) {
            case self::PROPERTY_APP_ID:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application ID for this provider from ' .
                        '"{1}" to "{2}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $old,
                            $new
                        ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application ID for this provider to ' .
                        '"{1}".',
                        [
                            $xaction->renderHandleLink($author_phid),
                            $new
                        ]);
                }
            case self::PROPERTY_APP_SECRET:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application secret for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application secret for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                }
            case self::PROPERTY_NOTE:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '{0} updated the OAuth application notes for this provider.',
                       [
                           $xaction->renderHandleLink($author_phid)
                       ]);
                } else {
                    return \Yii::t("app",
                        '{0} set the OAuth application notes for this provider.',
                        [
                            $xaction->renderHandleLink($author_phid)
                        ]);
                }

        }

        return parent::renderConfigPropertyTransactionTitle($xaction);
    }

    /**
     * @param AphrontRequest $request
     * @param AphrontFormView $form
     * @param array $values
     * @param array $issues
     * @param $id_label
     * @param $secret_label
     * @throws \Exception
     * @author 陈妙威
     */
    protected function extendOAuthEditForm(
        AphrontRequest $request,
        AphrontFormView $form,
        array $values,
        array $issues,
        $id_label,
        $secret_label)
    {

        $key_id = $this->getIDKey();
        $key_secret = $this->getSecretKey();
        $key_note = self::PROPERTY_NOTE;

        $v_id = $values[$key_id];
        $v_secret = $values[$key_secret];
        if ($v_secret) {
            $v_secret = str_repeat('*', strlen($v_secret));
        }
        $v_note = $values[$key_note];

        $e_id = ArrayHelper::getValue($issues, $key_id, $request->isFormPost() ? null : true);
        $e_secret = ArrayHelper::getValue($issues, $key_secret, $request->isFormPost() ? null : true);

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel($id_label)
                    ->setName($key_id)
                    ->setValue($v_id)
                    ->setError($e_id))
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setLabel($secret_label)
                    ->setDisableAutocomplete(true)
                    ->setName($key_secret)
                    ->setValue($v_secret)
                    ->setError($e_secret))
            ->appendChild(
                (new AphrontFormTextAreaControl())
                    ->setLabel(\Yii::t("app", 'OAuth App Notes'))
                    ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
                    ->setName($key_note)
                    ->setValue($v_note));
    }


    /**
     * @return PhabricatorWxampAuthAdapter
     * @author 陈妙威
     */
    protected function newOAuthAdapter()
    {
        return new PhabricatorWxampAuthAdapter();
    }

    /**
     * @return PhabricatorWxampAuthAdapter
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     */
    public function getAdapter()
    {

        if (!$this->adapter) {
            $adapter = $this->newOAuthAdapter();
            $this->adapter = $adapter;
            $this->configureAdapter($adapter);
        }
        return $this->adapter;
    }


    /**
     * @param PhutilAuthAdapter|PhabricatorWxampAuthAdapter $adapter
     * @return mixed
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     * @throws Exception
     */
    protected function configureAdapter(PhutilAuthAdapter $adapter)
    {
        $config = $this->getProviderConfig();
        $adapter->setClientID($config->getProperty(self::PROPERTY_APP_ID));
        $clientSecret = new PhutilOpaqueEnvelope($config->getProperty(self::PROPERTY_APP_SECRET));
        $adapter->setClientSecret($clientSecret);
        return $adapter;
    }

}
