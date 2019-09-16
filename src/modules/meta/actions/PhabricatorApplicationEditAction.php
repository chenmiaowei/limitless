<?php

namespace orangins\modules\meta\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;

/**
 * Class PhabricatorApplicationEditAction
 * @package orangins\modules\meta\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationEditAction
    extends PhabricatorApplicationsAction
{

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();
        $application = $request->getURIData('application');

        $application = (new PhabricatorApplicationQuery())
            ->setViewer($user)
            ->withClasses(array($application))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$application) {
            return new Aphront404Response();
        }

        $title = $application->getName();

        $view_uri = $this->getApplicationURI('view/' . get_class($application) . '/');

        $policies = PhabricatorPolicy::find()
            ->setViewer($user)
            ->setObject($application)
            ->execute();

        if ($request->isFormPost()) {
            $xactions = array();

            $template = $application->getApplicationTransactionTemplate();
            foreach ($application->getCapabilities() as $capability) {
                if (!$application->isCapabilityEditable($capability)) {
                    continue;
                }

                $old = $application->getPolicy($capability);
                $new = $request->getStr('policy:' . $capability);

                if ($old == $new) {
                    // No change to the setting.
                    continue;
                }

                $x = clone $template;
                $xactions[] = ($x)
                    ->setTransactionType(
                        PhabricatorApplicationPolicyChangeTransaction::TRANSACTIONTYPE)
                    ->setMetadataValue(
                        PhabricatorApplicationPolicyChangeTransaction::METADATA_ATTRIBUTE,
                        $capability)
                    ->setNewValue($new);
            }

            $editor = (new PhabricatorApplicationEditor())
                ->setActor($user)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true);

            try {
                $editor->applyTransactions($application, $xactions);
                return (new AphrontRedirectResponse())->setURI($view_uri);
            } catch (PhabricatorApplicationTransactionValidationException $ex) {
                $validation_exception = $ex;
            }

            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Validation Failed'))
                ->setValidationException($validation_exception)
                ->addCancelButton($view_uri);
        }

        $descriptions = PhabricatorPolicyQuery::renderPolicyDescriptions(
            $user,
            $application);

        $form = (new AphrontFormView())
            ->setUser($user);

        $locked_policies = PhabricatorEnv::getEnvConfig('policy.locked');
        foreach ($application->getCapabilities() as $capability) {
            $label = $application->getCapabilityLabel($capability);
            $can_edit = $application->isCapabilityEditable($capability);
            $locked = ArrayHelper::getValue($locked_policies, $capability);
            $caption = $application->getCapabilityCaption($capability);

            if (!$can_edit || $locked) {
                $form->appendChild(
                    (new AphrontFormStaticControl())
                        ->setLabel($label)
                        ->setValue(ArrayHelper::getValue($descriptions, $capability))
                        ->setCaption($caption));
            } else {
                $control = (new AphrontFormPolicyControl())
                    ->setUser($user)
                    ->setDisabled($locked)
                    ->setCapability($capability)
                    ->setPolicyObject($application)
                    ->setPolicies($policies)
                    ->setLabel($label)
                    ->setName('policy:' . $capability)
                    ->setCaption($caption);

                $template = $application->getCapabilityTemplatePHIDType($capability);
                if ($template) {
                    $phid_types = PhabricatorPHIDType::getAllTypes();
                    $phid_type = ArrayHelper::getValue($phid_types, $template);
                    if ($phid_type) {
                        $template_object = $phid_type->newObject();
                        if ($template_object) {
                            $template_policies = PhabricatorPolicy::find()
                                ->setViewer($user)
                                ->setObject($template_object)
                                ->execute();

                            // NOTE: We want to expose both any object template policies
                            // (like "Subscribers") and any custom policy.
                            $all_policies = $template_policies + $policies;

                            $control->setPolicies($all_policies);
                            $control->setTemplateObject($template_object);
                        }
                    }

                    $control->setTemplatePHIDType($template);
                }

                $form->appendControl($control);
            }

        }

        $form->appendChild(
            (new AphrontFormSubmitControl())
                ->setValue(\Yii::t("app", 'Save Policies'))
                ->addCancelButton($view_uri));

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($application->getName(), $view_uri);
        $crumbs->addTextCrumb(\Yii::t("app", 'Edit Policies'));
        $crumbs->setBorder(true);

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Edit Policies: {0}', [
                $application->getName()
            ]))
            ->setHeaderIcon('fa-pencil');

        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Policies'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter(array(
                $object_box,
            ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
