<?php

namespace orangins\modules\auth\actions;

use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\people\models\PhabricatorExternalAccount;

final class PhabricatorAuthUnlinkAction
    extends PhabricatorAuthAction
{

    private $providerKey;

    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $this->providerKey = $request->getURIData('pkey');

        list($type, $domain) = explode(':', $this->providerKey, 2);

        // Check that this account link actually exists. We don't require the
        // provider to exist because we want users to be able to delete links to
        // dead accounts if they want.
        $account = (new PhabricatorExternalAccount())->loadOneWhere(
            'accountType = %s AND accountDomain = %s AND userPHID = %s',
            $type,
            $domain,
            $viewer->getPHID());
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
            $other_accounts = (new PhabricatorExternalAccount())->loadAllWhere(
                'userPHID = %s',
                $viewer->getPHID());

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

    private function getDoneURI()
    {
        return '/settings/panel/external/';
    }

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
