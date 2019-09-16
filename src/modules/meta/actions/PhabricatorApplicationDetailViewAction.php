<?php

namespace orangins\modules\meta\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\PhabricatorApplication;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\meta\query\PhabricatorApplicationApplicationTransactionQuery;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorApplicationDetailViewAction
 * @package orangins\modules\meta\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationDetailViewAction
    extends PhabricatorApplicationsAction
{


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $application = $request->getURIData('application');

        $selected = (new PhabricatorApplicationQuery())
            ->withShortName(true)
            ->setViewer($viewer)
            ->withClasses(array($application))
            ->executeOne();
        if (!$selected) {
            return new Aphront404Response();
        }

        $title = $selected->getName();

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($selected->getName());
        $crumbs->setBorder(true);

        $header = (new PHUIPageHeaderView())
            ->setHeader($title)
            ->setUser($viewer)
            ->setPolicyObject($selected)
            ->setHeaderIcon($selected->getIcon());

        if ($selected->isInstalled()) {
            $header->setStatus('fa-check', AphrontView::COLOR_SUCCESS, \Yii::t("app", 'Installed'));
        } else {
            $header->setStatus('fa-ban', 'dark', \Yii::t("app", 'Uninstalled'));
        }

        $timeline = $this->buildTransactionTimeline(
            $selected,
            new PhabricatorApplicationApplicationTransactionQuery());
        $timeline->setShouldTerminate(true);

        $curtain = $this->buildCurtain($selected);
        $details = $this->buildPropertySectionView($selected);
        $policies = $this->buildPolicyView($selected);

        $configs =
            PhabricatorApplicationConfigurationPanel::loadAllPanelsForApplication(
                $selected);

        $panels = array();
        foreach ($configs as $config) {
            $config->setViewer($viewer);
            $config->setApplication($selected);
            $panel = $config->buildConfigurationPagePanel();
            $panel->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);
            $panels[] = $panel;
        }

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setCurtain($curtain)
            ->setMainColumn(array(
                $policies,
                $panels,
                $timeline,
            ))
            ->addPropertySection(\Yii::t("app", 'Details'), $details);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild(
                array(
                    $view,
                ));
    }

    /**
     * @param PhabricatorApplication $application
     * @return PHUIPropertyListView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertySectionView(
        PhabricatorApplication $application)
    {

        $viewer = $this->getViewer();
        $properties = (new PHUIPropertyListView());

        $properties->addProperty(
            \Yii::t("app", 'Description'),
            $application->getShortDescription());

        if ($application->getFlavorText()) {
            $properties->addProperty(
                null,
                phutil_tag('em', array(), $application->getFlavorText()));
        }

        if ($application->isPrototype()) {
            $proto_href = PhabricatorEnv::getDoclink(
                'User Guide: Prototype Applications');
            $learn_more = phutil_tag(
                'a',
                array(
                    'href' => $proto_href,
                    'target' => '_blank',
                ),
                \Yii::t("app", 'Learn More'));

            $properties->addProperty(
                \Yii::t("app", 'Prototype'),
                \Yii::t("app",
                    'This application is a prototype. {0}', [
                        $learn_more
                    ]));
        }

        $overview = $application->getOverview();
        if (strlen($overview)) {
            $overview = new PHUIRemarkupView($viewer, $overview);
            $properties->addSectionHeader(
                \Yii::t("app", 'Overview'), PHUIPropertyListView::ICON_SUMMARY);
            $properties->addTextContent($overview);
        }

        return $properties;
    }

    /**
     * @param PhabricatorApplication $application
     * @return PHUIObjectBoxView
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildPolicyView(
        PhabricatorApplication $application)
    {

        $viewer = $this->getViewer();
        $properties = (new PHUIPropertyListView());

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Policies'));

        $descriptions = PhabricatorPolicyQuery::renderPolicyDescriptions(
            $viewer,
            $application);

        foreach ($application->getCapabilities() as $capability) {
            $properties->addProperty(
                $application->getCapabilityLabel($capability),
                ArrayHelper::getValue($descriptions, $capability));
        }

        return (new PHUIObjectBoxView())
            ->setHeader($header)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($properties);

    }

    /**
     * @param PhabricatorApplication $application
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtain(PhabricatorApplication $application)
    {
        $viewer = $this->getViewer();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $application,
            PhabricatorPolicyCapability::CAN_EDIT);

        $key = get_class($application);
        $edit_uri = $this->getApplicationURI("edit/{$key}/");
        $install_uri = $this->getApplicationURI("{$key}/install/");
        $uninstall_uri = $this->getApplicationURI("{$key}/uninstall/");

        $curtain = $this->newCurtainView($application);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app", 'Edit Policies'))
                ->setIcon('fa-pencil')
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit)
                ->setHref($edit_uri));

        if ($application->canUninstall()) {
            if ($application->isInstalled()) {
                $curtain->addAction(
                    (new PhabricatorActionView())
                        ->setName(\Yii::t("app", 'Uninstall'))
                        ->setIcon('fa-times')
                        ->setDisabled(!$can_edit)
                        ->setWorkflow(true)
                        ->setHref($uninstall_uri));
            } else {
                $action = (new PhabricatorActionView())
                    ->setName(\Yii::t("app", 'Install'))
                    ->setIcon('fa-plus')
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true)
                    ->setHref($install_uri);

                $prototypes_enabled = PhabricatorEnv::getEnvConfig(
                    'phabricator.show-prototypes');
                if ($application->isPrototype() && !$prototypes_enabled) {
                    $action->setDisabled(true);
                }

                $curtain->addAction($action);
            }
        } else {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app", 'Uninstall'))
                    ->setIcon('fa-times')
                    ->setWorkflow(true)
                    ->setDisabled(true)
                    ->setHref($uninstall_uri));
        }

        return $curtain;
    }

}
