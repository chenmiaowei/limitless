<?php

namespace orangins\modules\auth\actions\config;

use orangins\modules\auth\capability\AuthManageProvidersCapability;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;

/**
 * Class PhabricatorAuthNewAction
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
final class PhabricatorAuthNewAction extends PhabricatorAuthProviderConfigAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $this->requireApplicationCapability(
            AuthManageProvidersCapability::CAPABILITY);
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $providers = PhabricatorAuthProvider::getAllBaseProviders();

        $e_provider = null;
        $errors = array();

        if ($request->isFormPost()) {
            $provider_string = $request->getStr('provider');
            if (!strlen($provider_string)) {
                $e_provider = \Yii::t("app", 'Required');
                $errors[] = \Yii::t("app", 'You must select an authentication provider.');
            } else {
                $found = false;
                foreach ($providers as $provider) {
                    if ($provider->getClassShortName() === $provider_string) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $e_provider = \Yii::t("app", 'Invalid');
                    $errors[] = \Yii::t("app", 'You must select a valid provider.');
                }
            }

            if (!$errors) {
                return (new AphrontRedirectResponse())
                    ->setURI($this->getApplicationURI('config/edit', [
                        'className' => $provider_string
                    ]));
            }
        }

        $options = (new AphrontFormRadioButtonControl())
            ->setLabel(\Yii::t("app", 'Provider'))
            ->setName('provider')
            ->setError($e_provider);

        $configured = PhabricatorAuthProvider::getAllProviders();
        $configured_classes = array();
        foreach ($configured as $configured_provider) {
            $configured_classes[get_class($configured_provider)] = true;
        }

        // Sort providers by login order, and move disabled providers to the
        // bottom.
        $providers = OranginsUtil::msort($providers, 'getLoginOrder');
        $providers = array_diff_key($providers, $configured_classes) + $providers;

        /** @var PhabricatorAuthProvider $provider */
        foreach ($providers as $provider) {
            if (isset($configured_classes[get_class($provider)])) {
                $disabled = true;
                $description = \Yii::t("app", 'This provider is already configured.');
            } else {
                $disabled = false;
                $description = $provider->getDescriptionForCreate();
            }
            $options->addButton(
                $provider->getClassShortName(),
                $provider->getNameForCreate(),
                $description,
                $disabled ? 'disabled' : null,
                $disabled);
        }

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendChild($options)
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($this->getApplicationURI())
                    ->setValue(\Yii::t("app", 'Continue')));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Provider'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Add Provider'));
        $crumbs->setBorder(true);

        $title = \Yii::t("app", 'Add Auth Provider');

        $header = (new PHUIPageHeaderView())
            ->setHeader($title)
            ->setHeaderIcon('fa-plus-square');

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $form_box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

}
