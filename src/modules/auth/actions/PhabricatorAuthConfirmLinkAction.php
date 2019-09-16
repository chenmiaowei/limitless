<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\modules\auth\view\PhabricatorAuthAccountView;
use orangins\modules\people\models\PhabricatorExternalAccount;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthConfirmLinkAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthConfirmLinkAction
    extends PhabricatorAuthAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
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
        $accountkey = $request->getURIData('akey');

        $result = $this->loadAccountForRegistrationOrLinking($accountkey);
        /** @var PhabricatorPasswordAuthProvider $provider  */
        /** @var PhabricatorExternalAccount $account  */
        list($account, $provider, $response) = $result;

        if ($response) {
            return $response;
        }

        if (!$provider->shouldAllowAccountLink()) {
            return $this->renderError(\Yii::t("app", 'This account is not linkable.'));
        }

//        Url::to(['/settings/index/panel', 'pageKey' => 'external']);
        $panel_uri = Url::to(['/settings/panel/external']);

        if ($request->isFormPost()) {
            $account->setUserPHID($viewer->getPHID());
            $account->save();

            $this->clearRegistrationCookies();

            // TODO: Send the user email about the new account link.

            return (new AphrontRedirectResponse())->setURI($panel_uri);
        }

        // TODO: Provide more information about the external account. Clicking
        // through this form blindly is dangerous.

        // TODO: If the user has password authentication, require them to retype
        // their password here.

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle(\Yii::t("app", 'Confirm %s Account Link', $provider->getProviderName()))
            ->addCancelButton($panel_uri)
            ->addSubmitButton(\Yii::t("app", 'Confirm Account Link'));

        $form = (new PHUIFormLayoutView())
            ->setFullWidth(true)
            ->appendChild(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'row aphront-form-instructions',
                    ),
                    \Yii::t("app",
                        'Confirm the link with this %s account. This account will be ' .
                        'able to log in to your Phabricator account.',
                        $provider->getProviderName())))
            ->appendChild(
                (new PhabricatorAuthAccountView())
                    ->setUser($viewer)
                    ->setExternalAccount($account)
                    ->setAuthProvider($provider));

        $dialog->appendChild($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Confirm Link'), $panel_uri);
        $crumbs->addTextCrumb($provider->getProviderName());
        $crumbs->setBorder(true);

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Confirm External Account Link'))
            ->setCrumbs($crumbs)
            ->appendChild($dialog);
    }


}
