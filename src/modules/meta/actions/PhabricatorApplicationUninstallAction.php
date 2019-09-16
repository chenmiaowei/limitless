<?php

namespace orangins\modules\meta\actions;

use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

final class PhabricatorApplicationUninstallAction
    extends PhabricatorApplicationsAction
{

    /**
     * @param AphrontRequest $request
     * @return Aphront404Response|AphrontDialogResponse|\orangins\lib\view\AphrontDialogView
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        {
            $viewer = $request->getViewer();
            $user = $request->getViewer();
            $action = $request->getURIData('action');
            $application_name = $request->getURIData('application');

            $application = (new PhabricatorApplicationQuery())
                ->setViewer($viewer)
                ->withClasses(array($application_name))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();

            if (!$application) {
                return new Aphront404Response();
            }

            $view_uri = $this->getApplicationURI('view/' . $application_name);

            $prototypes_enabled = PhabricatorEnv::getEnvConfig(
                'phabricator.show-prototypes');

            $dialog = (new AphrontDialogView())
                ->setUser($viewer)
                ->addCancelButton($view_uri);

            if ($application->isPrototype() && !$prototypes_enabled) {
                $dialog
                    ->setTitle(\Yii::t("app", 'Prototypes Not Enabled'))
                    ->appendChild(
                        \Yii::t("app",
                            'To manage prototypes, enable them by setting %s in your ' .
                            'Phabricator configuration.',
                            phutil_tag('tt', array(), 'phabricator.show-prototypes')));
                return (new AphrontDialogResponse())->setDialog($dialog);
            }

            if ($request->isDialogFormPost()) {
                $xactions = array();
                $template = $application->getApplicationTransactionTemplate();
                $x = clone $template;
                $xactions[] = ($x)
                    ->setTransactionType(
                        PhabricatorApplicationUninstallTransaction::TRANSACTIONTYPE)
                    ->setNewValue($action);

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

            if ($action == 'install') {
                if ($application->canUninstall()) {
                    $dialog
                        ->setTitle(\Yii::t("app", 'Confirmation'))
                        ->appendChild(
                            \Yii::t("app",
                                'Install %s application?',
                                $application->getName()))
                        ->addSubmitButton(\Yii::t("app", 'Install'));

                } else {
                    $dialog
                        ->setTitle(\Yii::t("app", 'Information'))
                        ->appendChild(\Yii::t("app", 'You cannot install an installed application.'));
                }
            } else {
                if ($application->canUninstall()) {
                    $dialog->setTitle(\Yii::t("app", 'Really Uninstall Application?'));

                    if ($application instanceof PhabricatorHomeApplication) {
                        $dialog
                            ->appendParagraph(
                                \Yii::t("app",
                                    'Are you absolutely certain you want to uninstall the Home ' .
                                    'application?'))
                            ->appendParagraph(
                                \Yii::t("app",
                                    'This is very unusual and will leave you without any ' .
                                    'content on the Phabricator home page. You should only ' .
                                    'do this if you are certain you know what you are doing.'))
                            ->addSubmitButton(\Yii::t("app", 'Completely Break Phabricator'));
                    } else {
                        $dialog
                            ->appendParagraph(
                                \Yii::t("app",
                                    'Really uninstall the %s application?',
                                    $application->getName()))
                            ->addSubmitButton(\Yii::t("app", 'Uninstall'));
                    }
                } else {
                    $dialog
                        ->setTitle(\Yii::t("app", 'Information'))
                        ->appendChild(
                            \Yii::t("app",
                                'This application cannot be uninstalled, ' .
                                'because it is required for Phabricator to work.'));
                }
            }
            return (new AphrontDialogResponse())->setDialog($dialog);
        }

    }
}
