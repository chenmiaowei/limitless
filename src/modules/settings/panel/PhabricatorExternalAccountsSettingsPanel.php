<?php

namespace orangins\modules\settings\panel;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\auth\query\PhabricatorExternalAccountQuery;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\settings\panelgroup\PhabricatorSettingsAuthenticationPanelGroup;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorExternalAccountsSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorExternalAccountsSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'external';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'External Accounts');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsAuthenticationPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $providers = PhabricatorAuthProvider::getAllProviders();

        $accounts = PhabricatorExternalAccount::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($viewer->getPHID()))
            ->needImages(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->execute();

        $linked_head = \Yii::t("app",'Linked Accounts and Authentication');

        $linked = (new PHUIObjectItemListView())
            ->setUser($viewer)
            ->setNoDataString(\Yii::t("app",'You have no linked accounts.'));

        $login_accounts = 0;
        foreach ($accounts as $account) {
            if ($account->isUsableForLogin()) {
                $login_accounts++;
            }
        }

        foreach ($accounts as $account) {
            $item = new PHUIObjectItemView();

            /** @var PhabricatorAuthProvider $provider */
            $provider = ArrayHelper::getValue($providers, $account->getProviderKey());
            if ($provider) {
                $item->setHeader($provider->getProviderName());
                $can_unlink = $provider->shouldAllowAccountUnlink();
                if (!$can_unlink) {
                    $item->addAttribute(\Yii::t("app",'Permanently Linked'));
                }
            } else {
                $item->setHeader(
                    \Yii::t("app",'Unknown Account ("%s")', $account->getProviderKey()));
                $can_unlink = true;
            }

            $can_login = $account->isUsableForLogin();
            if (!$can_login) {
                $item->addAttribute(
                    \Yii::t("app",
                        'Disabled (an administrator has disabled login for this ' .
                        'account provider).'));
            }

            $can_unlink = $can_unlink && (!$can_login || ($login_accounts > 1));

            $can_refresh = $provider && $provider->shouldAllowAccountRefresh();
            if ($can_refresh) {
                $item->addAction(
                    (new PHUIListItemView())
                        ->setIcon('fa-refresh')
                        ->setHref('/auth/refresh/' . $account->getProviderKey() . '/'));
            }

            $item->addAction(
                (new PHUIListItemView())
                    ->setIcon('fa-times')
                    ->setWorkflow(true)
                    ->setDisabled(!$can_unlink)
                    ->setHref('/auth/unlink/' . $account->getProviderKey() . '/'));

            if ($provider) {
                $provider->willRenderLinkedAccount($viewer, $item, $account);
            }

            $linked->addItem($item);
        }

        $linkable_head = \Yii::t("app",'Add External Account');

        $linkable = (new PHUIObjectItemListView())
            ->setUser($viewer)
            ->setNoDataString(
                \Yii::t("app",'Your account is linked with all available providers.'));

        $accounts = mpull($accounts, null, 'getProviderKey');

        /** @var PhabricatorAuthProvider[] $providers */
        $providers = PhabricatorAuthProvider::getAllEnabledProviders();
        $providers = msort($providers, 'getProviderName');
        foreach ($providers as $key => $provider) {
            if (isset($accounts[$key])) {
                continue;
            }

            if (!$provider->shouldAllowAccountLink()) {
                continue;
            }

            $link_uri = '/auth/link/' . $provider->getProviderKey() . '/';

            $item = (new PHUIObjectItemView())
                ->setHeader($provider->getProviderName())
                ->setHref($link_uri)
                ->addAction(
                    (new PHUIListItemView())
                        ->setIcon('fa-link')
                        ->setHref($link_uri));

            $linkable->addItem($item);
        }

        $linked_box = $this->newBox($linked_head, $linked);
        $linkable_box = $this->newBox($linkable_head, $linkable);

        return array(
            $linked_box,
            $linkable_box,
        );
    }

}
