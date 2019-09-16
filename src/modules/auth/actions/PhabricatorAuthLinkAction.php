<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\people\models\PhabricatorExternalAccount;

/**
 * Class PhabricatorAuthLinkAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthLinkAction
    extends PhabricatorAuthAction
{

    /**
     * @return Aphront400Response|Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $action = $request->getURIData('action');
        $provider_key = $request->getURIData('pkey');

        $provider = PhabricatorAuthProvider::getEnabledProviderByKey(
            $provider_key);
        if (!$provider) {
            return new Aphront404Response();
        }

        switch ($action) {
            case 'link':
                if (!$provider->shouldAllowAccountLink()) {
                    return $this->renderErrorPage(
                        \Yii::t("app", 'Account Not Linkable'),
                        array(
                            \Yii::t("app", 'This provider is not configured to allow linking.'),
                        ));
                }
                break;
            case 'refresh':
                if (!$provider->shouldAllowAccountRefresh()) {
                    return $this->renderErrorPage(
                        \Yii::t("app", 'Account Not Refreshable'),
                        array(
                            \Yii::t("app", 'This provider does not allow refreshing.'),
                        ));
                }
                break;
            default:
                return new Aphront400Response();
        }

        $account = PhabricatorExternalAccount::find()->andWhere([
            'account_type' => $provider->getProviderType(),
            'account_domain' => $provider->getProviderDomain(),
            'user_phid' => $viewer->getPHID(),
        ])->one();

        switch ($action) {
            case 'link':
                if ($account) {
                    return $this->renderErrorPage(
                        \Yii::t("app", 'Account Already Linked'),
                        array(
                            \Yii::t("app",
                                'Your Phabricator account is already linked to an external ' .
                                'account for this provider.'),
                        ));
                }
                break;
            case 'refresh':
                if (!$account) {
                    return $this->renderErrorPage(
                        \Yii::t("app", 'No Account Linked'),
                        array(
                            \Yii::t("app",
                                'You do not have a linked account on this provider, and thus ' .
                                'can not refresh it.'),
                        ));
                }
                break;
            default:
                return new Aphront400Response();
        }

        $panel_uri = '/settings/panel/external/';

        PhabricatorCookies::setClientIDCookie($request);

        switch ($action) {
            case 'link':
                (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
                    $viewer,
                    $request,
                    $panel_uri);

                $form = $provider->buildLinkForm($this);
                break;
            case 'refresh':
                $form = $provider->buildRefreshForm($this);
                break;
            default:
                return new Aphront400Response();
        }

        if ($provider->isLoginFormAButton()) {
//      require_celerity_resource('auth-css');
            $form = phutil_tag(
                'div',
                array(
                    'class' => 'phabricator-link-button pl',
                ),
                $form);
        }

        switch ($action) {
            case 'link':
                $name = \Yii::t("app", 'Link Account');
                $title = \Yii::t("app", 'Link %s Account', $provider->getProviderName());
                break;
            case 'refresh':
                $name = \Yii::t("app", 'Refresh Account');
                $title = \Yii::t("app", 'Refresh %s Account', $provider->getProviderName());
                break;
            default:
                return new Aphront400Response();
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Link Account'), $panel_uri);
        $crumbs->addTextCrumb($provider->getProviderName($name));
        $crumbs->setBorder(true);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($form);
    }

}
