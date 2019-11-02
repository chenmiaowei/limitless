<?php

namespace orangins\modules\auth\actions\config;

use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\auth\capability\AuthManageProvidersCapability;
use orangins\modules\auth\editor\PhabricatorAuthProviderConfigEditor;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\PHUIFormDividerControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITagView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthEditAction
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
final class PhabricatorAuthEditAction extends PhabricatorAuthProviderConfigAction
{

    /**
     * @return PhabricatorStandardPageView|AphrontResponse
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws Exception
     * @throws InvalidConfigException *@throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $this->requireApplicationCapability(
            AuthManageProvidersCapability::CAPABILITY);
        $viewer = $request->getViewer();
        $provider_class = $request->getURIData('className');
        $config_id = $request->getURIData('id');

        if ($config_id) {
            $config = PhabricatorAuthProviderConfig::find()
                ->withIDs(array($config_id))
                ->setViewer($viewer)
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();
            if (!$config) {
                return new Aphront404Response();
            }

            $provider = $config->getProvider();
            if (!$provider) {
                return new Aphront404Response();
            }

            $is_new = false;
        } else {
            $provider = null;

            $providers = PhabricatorAuthProvider::getAllBaseProviders();
            foreach ($providers as $candidate_provider) {
                if ($candidate_provider->getClassShortName() === $provider_class) {
                    $provider = $candidate_provider;
                    break;
                }
            }

            if (!$provider) {
                return new Aphront404Response();
            }

            // TODO: When we have multi-auth providers, support them here.

            $configs = PhabricatorAuthProviderConfig::find()
                ->setViewer($viewer)
                ->withProviderClasses(array($provider->getClassShortName()))
                ->execute();

            if ($configs) {
                $id = OranginsUtil::head($configs)->getID();
                $dialog = (new AphrontDialogView())
                    ->setUser($viewer)
                    ->setMethod('GET')
                    ->setSubmitURI($this->getApplicationURI('config/edit', ['id' => $id]))
                    ->setTitle(Yii::t("app", 'Provider Already Configured'))
                    ->appendChild(
                        Yii::t("app",
                            'This provider ("{0}") already exists, and you can not add more ' .
                            'than one instance of it. You can edit the existing provider, ' .
                            'or you can choose a different provider.', [
                                $provider->getProviderName()
                            ]))
                    ->addCancelButton($this->getApplicationURI('config/new'))
                    ->addSubmitButton(Yii::t("app", 'Edit Existing Provider'));

                return (new AphrontDialogResponse())->setDialog($dialog);
            }

            $config = $provider->getDefaultProviderConfig();
            $provider->attachProviderConfig($config);

            $is_new = true;
        }

        $errors = array();

        $v_login = $config->getShouldAllowLogin();
        $v_registration = $config->getShouldAllowRegistration();
        $v_link = $config->getShouldAllowLink();
        $v_unlink = $config->getShouldAllowUnlink();
        $v_trust_email = $config->getShouldTrustEmails();
        $v_auto_login = $config->getShouldAutoLogin();

        if ($request->isFormPost()) {

            $properties = $provider->readFormValuesFromRequest($request);
            list($errors, $issues, $properties) = $provider->processEditForm(
                $request,
                $properties);

            $xactions = array();

            if (!$errors) {
                if ($is_new) {
                    if (!strlen($config->getProviderType())) {
                        $config->setProviderType($provider->getProviderType());
                    }
                    if (!strlen($config->getProviderDomain())) {
                        $config->setProviderDomain($provider->getProviderDomain());
                    }
                }

                $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                    ->setTransactionType(
                        PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN)
                    ->setNewValue($request->getInt('allowLogin', 0));

                $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                    ->setTransactionType(
                        PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION)
                    ->setNewValue($request->getInt('allowRegistration', 0));

                $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                    ->setTransactionType(
                        PhabricatorAuthProviderConfigTransaction::TYPE_LINK)
                    ->setNewValue($request->getInt('allowLink', 0));

                $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                    ->setTransactionType(
                        PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK)
                    ->setNewValue($request->getInt('allowUnlink', 0));

                $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                    ->setTransactionType(
                        PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS)
                    ->setNewValue($request->getInt('trustEmails', 0));

                if ($provider->supportsAutoLogin()) {
                    $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                        ->setTransactionType(
                            PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN)
                        ->setNewValue($request->getInt('autoLogin', 0));
                }

                foreach ($properties as $key => $value) {
                    $xactions[] = (new PhabricatorAuthProviderConfigTransaction())
                        ->setTransactionType(
                            PhabricatorAuthProviderConfigTransaction::TYPE_PROPERTY)
                        ->setMetadataValue('auth:property', $key)
                        ->setNewValue($value);
                }

                if ($is_new) {
                    $config->save();
                }

                $editor = (new PhabricatorAuthProviderConfigEditor())
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnNoEffect(true)
                    ->applyTransactions($config, $xactions);

                if ($provider->hasSetupStep() && $is_new) {
                    $id = $config->getID();
                    $next_uri = $this->getApplicationURI('config/edit', [
                        'id' => $id
                    ]);
                } else {
                    $next_uri = $this->getApplicationURI();
                }

                return (new AphrontRedirectResponse())->setURI($next_uri);
            }
        } else {
            $properties = $provider->readFormValuesFromProvider();
            $issues = array();
        }

        if ($is_new) {
            if ($provider->hasSetupStep()) {
                $button = Yii::t("app", 'Next Step');
            } else {
                $button = Yii::t("app", 'Add Provider');
            }
            $crumb = Yii::t("app", 'Add Provider');
            $title = Yii::t("app", 'Add Auth Provider');
            $header_icon = 'fa-plus-square';
            $cancel_uri = $this->getApplicationURI('/config/new/');
        } else {
            $button = Yii::t("app", 'Save');
            $crumb = Yii::t("app", 'Edit Provider');
            $title = Yii::t("app", 'Edit Auth Provider');
            $header_icon = 'fa-pencil';
            $cancel_uri = $this->getApplicationURI();
        }

        $header = (new PHUIPageHeaderView())
            ->setHeader(Yii::t("app", '{0}: {1}', [$title, $provider->getProviderName()]))
            ->setHeaderIcon($header_icon);

        if (!$is_new) {
            if ($config->getIsEnabled()) {
                $status_name = Yii::t("app", 'Enabled');
                $status_color = PHUITagView::COLOR_GREEN;
                $status_icon = 'fa-check';
                $header->setStatus($status_icon, $status_color, $status_name);
            } else {
                $status_name = Yii::t("app", 'Disabled');
                $status_color = PHUITagView::COLOR_INDIGO;
                $status_icon = 'fa-ban';
                $header->setStatus($status_icon, $status_color, $status_name);
            }
        }

        $config_name = 'auth.email-domains';
        $config_href = Url::to(['/config/index/edit', 'key' => $config_name]);

        $email_domains = PhabricatorEnv::getEnvConfig($config_name);
        if ($email_domains) {
            $registration_warning = Yii::t("app",
                'Users will only be able to register with a verified email address ' .
                'at one of the configured [[ {0} | {1} ]] domains: **{2}**', [
                    $config_href,
                    $config_name,
                    implode(', ', $email_domains)
                ]);
        } else {
            $registration_warning = Yii::t("app",
                "NOTE: Any user who can browse to this install's login page will be " .
                "able to register a Phabricator account. To restrict who can register " .
                "an account, configure [[ {0} | {1} ]].", [
                    $config_href,
                    $config_name
                ]);
        }

        $str_login = array(
            JavelinHtml::phutil_tag('strong', array(), Yii::t("app", 'Allow Login:')),
            ' ',
            Yii::t("app",
                'Allow users to log in using this provider. If you disable login, ' .
                'users can still use account integrations for this provider.'),
        );

        $str_registration = array(
            JavelinHtml::phutil_tag('strong', array(), Yii::t("app", 'Allow Registration:')),
            ' ',
            Yii::t("app",
                'Allow users to register new Phabricator accounts using this ' .
                'provider. If you disable registration, users can still use this ' .
                'provider to log in to existing accounts, but will not be able to ' .
                'create new accounts.'),
        );

        $str_link = JavelinHtml::hsprintf(
            '<strong>%s:</strong> %s',
            Yii::t("app", 'Allow Linking Accounts'),
            Yii::t("app",
                'Allow users to link account credentials for this provider to ' .
                'existing Phabricator accounts. There is normally no reason to ' .
                'disable this unless you are trying to move away from a provider ' .
                'and want to stop users from creating new account links.'));

        $str_unlink = JavelinHtml::hsprintf(
            '<strong>%s:</strong> %s',
            Yii::t("app", 'Allow Unlinking Accounts'),
            Yii::t("app",
                'Allow users to unlink account credentials for this provider from ' .
                'existing Phabricator accounts. If you disable this, Phabricator ' .
                'accounts will be permanently bound to provider accounts.'));

        $str_trusted_email = JavelinHtml::hsprintf(
            '<strong>%s:</strong> %s',
            Yii::t("app", 'Trust Email Addresses'),
            Yii::t("app",
                'Phabricator will skip email verification for accounts registered ' .
                'through this provider.'));
        $str_auto_login = JavelinHtml::hsprintf(
            '<strong>%s:</strong> %s',
            Yii::t("app", 'Allow Auto Login'),
            Yii::t("app",
                'Phabricator will automatically login with this provider if it is ' .
                'the only available provider.'));

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->setLabel(Yii::t("app", 'Allow'))
                    ->addCheckbox(
                        'allowLogin',
                        1,
                        $str_login,
                        $v_login))
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'allowRegistration',
                        1,
                        $str_registration,
                        $v_registration))
            ->appendRemarkupInstructions($registration_warning)
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'allowLink',
                        1,
                        $str_link,
                        $v_link))
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'allowUnlink',
                        1,
                        $str_unlink,
                        $v_unlink));

        if ($provider->shouldAllowEmailTrustConfiguration()) {
            $form->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'trustEmails',
                        1,
                        $str_trusted_email,
                        $v_trust_email));
        }

        if ($provider->supportsAutoLogin()) {
            $form->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'autoLogin',
                        1,
                        $str_auto_login,
                        $v_auto_login));
        }

        $provider->extendEditForm($request, $form, $properties, $issues);

        $form
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($cancel_uri)
                    ->setValue($button));

        $help = $provider->getConfigurationHelp();
        if ($help) {
            $form->appendChild((new PHUIFormDividerControl()));
            $form->appendRemarkupInstructions($help);
        }

        $footer = $provider->renderConfigurationFooter();

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($crumb);
        $crumbs->setBorder(true);

        $timeline = null;
        if (!$is_new) {
            $timeline = $this->buildTransactionTimeline(
                $config,
                PhabricatorAuthProviderConfigTransaction::find());
            $xactions = $timeline->getTransactions();
            foreach ($xactions as $xaction) {
                $xaction->setProvider($provider);
            }
            $timeline->setShouldTerminate(true);
        }

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(Yii::t("app", 'Provider'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $form_box,
                $footer,
                $timeline,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

}
