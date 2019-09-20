<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\people\models\PhabricatorExternalAccount;

/**
 * Class PhabricatorAuthUnlinkAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthUnlinkAction
    extends PhabricatorAuthAction
{

    /**
     * @var
     */
    private $providerKey;

    /**
     * @return AphrontDialogResponse|AphrontRedirectResponse
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $this->providerKey = $request->getURIData('pkey');

        list($type, $domain) = explode(':', $this->providerKey, 2);

        // Check that this account link actually exists. We don't require the
        // provider to exist because we want users to be able to delete links to
        // dead accounts if they want.
        /** @var PhabricatorExternalAccount $account */
        $account = PhabricatorExternalAccount::find()->andWhere([
            'account_type' => $type,
            'account_domain' => $domain,
            'user_phid' => $viewer->getPHID()
        ])->one();
        if (!$account) {
            return $this->renderNoAccountErrorDialog();
        }

        // Check that the provider (if it exists) allows accounts to be unlinked.
        $provider_key = $this->providerKey;
        $provider = PhabricatorAuthProvider::getEnabledProviderByKey($provider_key);
        if ($provider) {
            if (!$provider->shouldAllowAccountUnlink()) {
                return $this->renderNotUnlinkableErrorDialog($provider);
            }
        }

        // Check that this account isn't the last account which can be used to
        // login. We prevent you from removing the last account.
        if ($account->isUsableForLogin()) {
            /** @var PhabricatorExternalAccount $other_accounts */
            $other_accounts = PhabricatorExternalAccount::find()->andWhere(['user_phid' =>  $viewer->getPHID()])->all();
            $valid_accounts = 0;
            foreach ($other_accounts as $other_account) {
                if ($other_account->isUsableForLogin()) {
                    $valid_accounts++;
                }
            }

            if ($valid_accounts < 2) {
                return $this->renderLastUsableAccountErrorDialog();
            }
        }

        if ($request->isDialogFormPost()) {
            $account->delete();

            (new PhabricatorAuthSessionEngine())->terminateLoginSessions(
                $viewer,
                $request->getCookie(PhabricatorCookies::COOKIE_SESSION));

            return (new AphrontRedirectResponse())->setURI($this->getDoneURI());
        }

        return $this->renderConfirmDialog();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDoneURI()
    {
        return '/settings/panel/external/';
    }

    /**
     * @return AphrontDialogResponse
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderNoAccountErrorDialog()
    {
        $dialog = (new AphrontDialogView())
            ->setUser($this->getRequest()->getViewer())
            ->setTitle(\Yii::t("app", 'No Such Account'))
            ->appendChild(
                \Yii::t("app",
                    'You can not unlink this account because it is not linked.'))
            ->addCancelButton($this->getDoneURI());

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @param PhabricatorAuthProvider $provider
     * @return AphrontDialogResponse
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderNotUnlinkableErrorDialog(
        PhabricatorAuthProvider $provider)
    {

        $dialog = (new AphrontDialogView())
            ->setUser($this->getRequest()->getViewer())
            ->setTitle(\Yii::t("app", 'Permanent Account Link'))
            ->appendChild(
                \Yii::t("app",
                    'You can not unlink this account because the administrator has ' .
                    'configured Phabricator to make links to %s accounts permanent.',
                    $provider->getProviderName()))
            ->addCancelButton($this->getDoneURI());

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @return AphrontDialogResponse
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderLastUsableAccountErrorDialog()
    {
        $dialog = (new AphrontDialogView())
            ->setUser($this->getRequest()->getViewer())
            ->setTitle(\Yii::t("app", 'Last Valid Account'))
            ->appendChild(
                \Yii::t("app",
                    'You can not unlink this account because you have no other ' .
                    'valid login accounts. If you removed it, you would be unable ' .
                    'to log in. Add another authentication method before removing ' .
                    'this one.'))
            ->addCancelButton($this->getDoneURI());

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @return AphrontDialogResponse
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderConfirmDialog()
    {
        $provider_key = $this->providerKey;
        $provider = PhabricatorAuthProvider::getEnabledProviderByKey($provider_key);

        if ($provider) {
            $title = \Yii::t("app", 'Unlink "%s" Account?', $provider->getProviderName());
            $body = \Yii::t("app",
                'You will no longer be able to use your %s account to ' .
                'log in to Phabricator.',
                $provider->getProviderName());
        } else {
            $title = \Yii::t("app", 'Unlink Account?');
            $body = \Yii::t("app",
                'You will no longer be able to use this account to log in ' .
                'to Phabricator.');
        }

        $dialog = (new AphrontDialogView())
            ->setUser($this->getRequest()->getViewer())
            ->setTitle($title)
            ->appendParagraph($body)
            ->appendParagraph(
                \Yii::t("app",
                    'Note: Unlinking an authentication provider will terminate any ' .
                    'other active login sessions.'))
            ->addSubmitButton(\Yii::t("app", 'Unlink Account'))
            ->addCancelButton($this->getDoneURI());

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}
