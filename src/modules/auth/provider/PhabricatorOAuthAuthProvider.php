<?php

namespace orangins\modules\auth\provider;

use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\people\models\PhabricatorExternalAccount;
use PhutilAuthAdapter;
use PhutilOAuthAuthAdapter;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorOAuthAuthProvider
 * @package orangins\modules\auth\provider
 * @author 陈妙威
 */
abstract class PhabricatorOAuthAuthProvider extends PhabricatorAuthProvider
{

    /**
     *
     */
    const PROPERTY_NOTE = 'oauth:app:note';

    /**
     * @var
     */
    protected $adapter;

    /**
     * @return PhutilOAuthAuthAdapter
     * @author 陈妙威
     */
    abstract protected function newOAuthAdapter();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract protected function getIDKey();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract protected function getSecretKey();


    /**
     * @param PhutilAuthAdapter $adapter
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function configureAdapter(PhutilAuthAdapter $adapter);

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDescriptionForCreate()
    {
        return \Yii::t("app", 'Configure {0} OAuth.', [$this->getProviderName()]);
    }

    /**
     * @return PhutilAuthAdapter
     * @author 陈妙威
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
     * @return bool
     * @author 陈妙威
     */
    public function isLoginFormAButton()
    {
        return true;
    }

    /**
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
     * @param AphrontRequest $request
     * @param array $values
     * @param $id_error
     * @param $secret_error
     * @return array
     * @author 陈妙威
     */
    protected function processOAuthEditForm(
        AphrontRequest $request,
        array $values,
        $id_error,
        $secret_error)
    {

        $errors = array();
        $issues = array();
        $key_id = $this->getIDKey();
        $key_secret = $this->getSecretKey();

        if (!strlen($values[$key_id])) {
            $errors[] = $id_error;
            $issues[$key_id] = \Yii::t("app", 'Required');
        }

        if (!strlen($values[$key_secret])) {
            $errors[] = $secret_error;
            $issues[$key_secret] = \Yii::t("app", 'Required');
        }

        // If the user has not changed the secret, don't update it (that is,
        // don't cause a bunch of "****" to be written to the database).
        if (preg_match('/^[*]+$/', $values[$key_secret])) {
            unset($values[$key_secret]);
        }

        return array($errors, $issues, $values);
    }

    /**
     * @return null|string
     * @author 陈妙威
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
     */
    abstract protected function getProviderConfigurationHelp();

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
     * @param PhabricatorAuthProviderConfigTransaction $xaction
     * @return null|string
     * @throws \PhutilJSONParserException
     * @throws \Exception
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
     * @param PhabricatorExternalAccount $account
     * @author 陈妙威
     */
    protected function willSaveAccount(PhabricatorExternalAccount $account)
    {
        parent::willSaveAccount($account);
        $this->synchronizeOAuthAccount($account);
    }

    /**
     * @param PhabricatorExternalAccount $account
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function synchronizeOAuthAccount(
        PhabricatorExternalAccount $account);

}
