<?php

namespace orangins\modules\settings\panel;

use AphrontDuplicateKeyQueryException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontReloadResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\settings\panelgroup\PhabricatorSettingsEmailPanelGroup;
use orangins\modules\settings\systemaction\PhabricatorSettingsAddEmailAction;
use orangins\modules\system\engine\PhabricatorSystemActionEngine;
use PhutilURI;

/**
 * Class PhabricatorEmailAddressesSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorEmailAddressesSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'email';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Email Addresses');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsEmailPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEditableByAdministrators()
    {
        if ($this->getUser()->getIsMailingList()) {
            return true;
        }

        return false;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \orangins\modules\system\exception\PhabricatorSystemActionRateLimitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $user = $this->getUser();
        $editable = PhabricatorEnv::getEnvConfig('account.editable');

        $uri = $request->getRequestURI();
        $uri->setQueryParams(array());

        if ($editable) {
            $new = $request->getStr('new');
            if ($new) {
                return $this->returnNewAddressResponse($request, $uri, $new);
            }

            $delete = $request->getInt('delete');
            if ($delete) {
                return $this->returnDeleteAddressResponse($request, $uri, $delete);
            }
        }

        $verify = $request->getInt('verify');
        if ($verify) {
            return $this->returnVerifyAddressResponse($request, $uri, $verify);
        }

        $primary = $request->getInt('primary');
        if ($primary) {
            return $this->returnPrimaryAddressResponse($request, $uri, $primary);
        }


        /** @var PhabricatorUserEmail[] $emails */
        $emails = PhabricatorUserEmail::find()->andWhere(['user_phid' => $user->getPHID()])->orderBy("address asc")->all();


        $rowc = array();
        $rows = array();
        foreach ($emails as $email) {
            $button_verify = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button small button-grey',
                    'href' => $uri->alter('verify', $email->getID()),
                    'sigil' => 'workflow',
                ),
                \Yii::t("app",'Verify'));

            $button_make_primary = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button small button-grey',
                    'href' => $uri->alter('primary', $email->getID()),
                    'sigil' => 'workflow',
                ),
                \Yii::t("app",'Make Primary'));

            $button_remove = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button small button-grey',
                    'href' => $uri->alter('delete', $email->getID()),
                    'sigil' => 'workflow',
                ),
                \Yii::t("app",'Remove'));

            $button_primary = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button small disabled',
                ),
                \Yii::t("app",'Primary'));

            if (!$email->getIsVerified()) {
                $action = $button_verify;
            } else if ($email->getIsPrimary()) {
                $action = $button_primary;
            } else {
                $action = $button_make_primary;
            }

            if ($email->getIsPrimary()) {
                $remove = $button_primary;
                $rowc[] = 'highlighted';
            } else {
                $remove = $button_remove;
                $rowc[] = null;
            }

            $rows[] = array(
                $email->getAddress(),
                $action,
                $remove,
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
                \Yii::t("app",'Email'),
                \Yii::t("app",'Status'),
                \Yii::t("app",'Remove'),
            ));
        $table->setColumnClasses(
            array(
                'wide',
                'action',
                'action',
            ));
        $table->setRowClasses($rowc);
        $table->setColumnVisibility(
            array(
                true,
                true,
                $editable,
            ));

        $buttons = array();
        if ($editable) {
            $buttons[] = (new PHUIButtonView())
                ->setTag('a')
                ->setIcon('fa-plus')
                ->setText(\Yii::t("app",'Add New Address'))
                ->setHref($uri->alter('new', 'true'))
                ->addSigil('workflow')
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));
        }

        return $this->newBox(\Yii::t("app",'Email Addresses'), $table, $buttons);
    }

    /**
     * @param AphrontRequest $request
     * @param PhutilURI $uri
     * @param $new
     * @return mixed
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\modules\system\exception\PhabricatorSystemActionRateLimitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    private function returnNewAddressResponse(
        AphrontRequest $request,
        PhutilURI $uri,
        $new)
    {

        $user = $this->getUser();
        $viewer = $this->getViewer();

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $this->getPanelURI());

        $e_email = true;
        $email = null;
        $errors = array();
        if ($request->isDialogFormPost()) {
            $email = trim($request->getStr('email'));

            if ($new == 'verify') {
                // The user clicked "Done" from the "an email has been sent" dialog.
                return (new AphrontReloadResponse())->setURI($uri);
            }

            PhabricatorSystemActionEngine::willTakeAction(
                array($viewer->getPHID()),
                new PhabricatorSettingsAddEmailAction(),
                1);

            if (!strlen($email)) {
                $e_email = \Yii::t("app",'Required');
                $errors[] = \Yii::t("app",'Email is required.');
            } else if (!PhabricatorUserEmail::isValidAddress($email)) {
                $e_email = \Yii::t("app",'Invalid');
                $errors[] = PhabricatorUserEmail::describeValidAddresses();
            } else if (!PhabricatorUserEmail::isAllowedAddress($email)) {
                $e_email = \Yii::t("app",'Disallowed');
                $errors[] = PhabricatorUserEmail::describeAllowedAddresses();
            }
            if ($e_email === true) {
                $application_email = PhabricatorMetaMTAApplicationEmail::find()
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withAddresses(array($email))
                    ->executeOne();
                if ($application_email) {
                    $e_email = \Yii::t("app",'In Use');
                    $errors[] = $application_email->getInUseMessage();
                }
            }

            if (!$errors) {
                $object = (new PhabricatorUserEmail())
                    ->setAddress($email)
                    ->setIsVerified(0);

                // If an administrator is editing a mailing list, automatically verify
                // the address.
                if ($viewer->getPHID() != $user->getPHID()) {
                    if ($viewer->getIsAdmin()) {
                        $object->setIsVerified(1);
                    }
                }

                try {
                    (new PhabricatorUserEditor())
                        ->setActor($viewer)
                        ->addEmail($user, $object);

                    if ($object->getIsVerified()) {
                        // If we autoverified the address, just reload the page.
                        return (new AphrontReloadResponse())->setURI($uri);
                    }

                    $object->sendVerificationEmail($user);

                    $dialog = $this->newDialog()
                        ->addHiddenInput('new', 'verify')
                        ->setTitle(\Yii::t("app",'Verification Email Sent'))
                        ->appendChild(JavelinHtml::phutil_tag('p', array(), \Yii::t("app",
                            'A verification email has been sent. Click the link in the ' .
                            'email to verify your address.')))
                        ->setSubmitURI($uri)
                        ->addSubmitButton(\Yii::t("app",'Done'));

                    return (new AphrontDialogResponse())->setDialog($dialog);
                } catch (AphrontDuplicateKeyQueryException $ex) {
                    $e_email = \Yii::t("app",'Duplicate');
                    $errors[] = \Yii::t("app",'Another user already has this email.');
                }
            }
        }

        if ($errors) {
            $errors = (new PHUIInfoView())
                ->setErrors($errors);
        }

        $form = (new PHUIFormLayoutView())
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app",'Email'))
                    ->setName('email')
                    ->setValue($email)
                    ->setCaption(PhabricatorUserEmail::describeAllowedAddresses())
                    ->setError($e_email));

        $dialog = $this->newDialog()
            ->addHiddenInput('new', 'true')
            ->setTitle(\Yii::t("app",'New Address'))
            ->appendChild($errors)
            ->appendChild($form)
            ->addSubmitButton(\Yii::t("app",'Save'))
            ->addCancelButton($uri);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @param PhutilURI $uri
     * @param $email_id
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    private function returnDeleteAddressResponse(
        AphrontRequest $request,
        PhutilURI $uri,
        $email_id)
    {
        $user = $this->getUser();
        $viewer = $this->getViewer();

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $this->getPanelURI());

        // NOTE: You can only delete your own email addresses, and you can not
        // delete your primary address.
        /** @var PhabricatorUserEmail $email */
        $email = PhabricatorUserEmail::find()->andWhere([
            'id' => $email_id,
            'user_phid' => $user->getPHID(),
            'is_primary' => 0
        ])->one();


        if (!$email) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {
            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->removeEmail($user, $email);

            return (new AphrontRedirectResponse())->setURI($uri);
        }

        $address = $email->getAddress();

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('delete', $email_id)
            ->setTitle(\Yii::t("app","Really delete address '%s'?", $address))
            ->appendParagraph(
                \Yii::t("app",
                    'Are you sure you want to delete this address? You will no ' .
                    'longer be able to use it to login.'))
            ->appendParagraph(
                \Yii::t("app",
                    'Note: Removing an email address from your account will invalidate ' .
                    'any outstanding password reset links.'))
            ->addSubmitButton(\Yii::t("app",'Delete'))
            ->addCancelButton($uri);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @param PhutilURI $uri
     * @param $email_id
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
     * @throws \Exception
     * @author 陈妙威
     */
    private function returnVerifyAddressResponse(
        AphrontRequest $request,
        PhutilURI $uri,
        $email_id)
    {
        $user = $this->getUser();
        $viewer = $this->getViewer();

        // NOTE: You can only send more email for your unverified addresses.
        /** @var PhabricatorUserEmail $email */
        $email = PhabricatorUserEmail::find()->andWhere([
            'id' => $email_id,
            'user_phid' => $user->getPHID(),
            'is_verified' => 0
        ])->one();

        if (!$email) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {
            $email->sendVerificationEmail($user);
            return (new AphrontRedirectResponse())->setURI($uri);
        }

        $address = $email->getAddress();

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('verify', $email_id)
            ->setTitle(\Yii::t("app",'Send Another Verification Email?'))
            ->appendChild(JavelinHtml::phutil_tag('p', array(), \Yii::t("app",
                'Send another copy of the verification email to %s?',
                $address)))
            ->addSubmitButton(\Yii::t("app",'Send Email'))
            ->addCancelButton($uri);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @param PhutilURI $uri
     * @param $email_id
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse
     * @throws AphrontDuplicateKeyQueryException
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    private function returnPrimaryAddressResponse(
        AphrontRequest $request,
        PhutilURI $uri,
        $email_id)
    {
        $user = $this->getUser();
        $viewer = $this->getViewer();

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $this->getPanelURI());

        // NOTE: You can only make your own verified addresses primary.
        /** @var PhabricatorUserEmail $email */
        $email = PhabricatorUserEmail::find()->andWhere([
            'id' => $email_id,
            'user_phid' => $user->getPHID(),
            'is_verified' => 1,
            'is_primary' => 0
        ])->one();

        if (!$email) {
            return new Aphront404Response();
        }

        if ($request->isFormPost()) {
            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->changePrimaryEmail($user, $email);

            return (new AphrontRedirectResponse())->setURI($uri);
        }

        $address = $email->getAddress();

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->addHiddenInput('primary', $email_id)
            ->setTitle(\Yii::t("app",'Change primary email address?'))
            ->appendParagraph(
                \Yii::t("app",
                    'If you change your primary address, Phabricator will send all ' .
                    'email to %s.',
                    $address))
            ->appendParagraph(
                \Yii::t("app",
                    'Note: Changing your primary email address will invalidate any ' .
                    'outstanding password reset links.'))
            ->addSubmitButton(\Yii::t("app",'Change Primary Address'))
            ->addCancelButton($uri);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}
