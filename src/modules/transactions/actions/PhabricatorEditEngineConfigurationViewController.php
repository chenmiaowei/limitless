<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use yii\helpers\Url;

/**
 * Class PhabricatorEditEngineConfigurationViewController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationViewController
    extends PhabricatorEditEngineController
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

        $config = $this->loadConfigForView();
        if (!$config) {
            return (new Aphront404Response());
        }

        $is_concrete = (bool)$config->getID();

        $curtain = $this->buildCurtainView($config);
        $properties = $this->buildPropertyView($config);

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setPolicyObject($config)
            ->setHeader(\Yii::t("app",'Edit Form: {0}', [$config->getDisplayName()]))
            ->setHeaderIcon('fa-pencil');

        if ($config->getIsDisabled()) {
            $name = \Yii::t("app",'Disabled');
            $icon = 'fa-ban';
            $color = 'indigo';
        } else {
            $name = \Yii::t("app",'Enabled');
            $icon = 'fa-check';
            $color = 'green';
        }
        $header->setStatus($icon, $color, $name);

        $field_list = $this->buildFieldList($config);
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->setBorder(true);

        if ($is_concrete) {
            $title = \Yii::t("app",'Form %d', $config->getID());
            $crumbs->addTextCrumb($title);
        } else {
            $title = \Yii::t("app",'Builtin');
            $crumbs->addTextCrumb(\Yii::t("app",'Builtin'));
        }

        if ($is_concrete) {
            $timeline = $this->buildTransactionTimeline(
                $config,
                PhabricatorEditEngineConfigurationTransaction::find());

            $timeline->setShouldTerminate(true);
        } else {
            $timeline = null;
        }

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(array(
                $field_list,
                $timeline,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorEditEngineConfiguration $config
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(
        PhabricatorEditEngineConfiguration $config)
    {
        $viewer = $this->getViewer();
        $engine = $config->getEngine();
        $engine_key = $engine->getEngineKey();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $config,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain = $this->newCurtainView($config);
        $form_key = $config->getIdentifier();

        $is_concrete = (bool)$config->getID();
        if (!$is_concrete) {
            $save_uri = Url::to([
                "/transactions/editengine/save",
                "engineKey" => $engine_key,
                "key" => $form_key,
            ]);

            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'Make Editable'))
                    ->setIcon('fa-pencil')
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(true)
                    ->setHref($save_uri));

            $can_edit = false;
        } else {
            $edit_uri = Url::to([
                "/transactions/editengine/edit",
                "engineKey" => $engine_key,
                "key" => $form_key,
            ]);
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'Edit Form Configuration'))
                    ->setIcon('fa-pencil')
                    ->setDisabled(!$can_edit)
                    ->setWorkflow(!$can_edit)
                    ->setHref($edit_uri));
        }

        $use_uri = $engine->getEditURI(null, ['formKey' => $form_key]);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Use Form'))
                ->setIcon('fa-th-list')
                ->setHref($use_uri));

        $defaults_uri = Url::to([
            "/transactions/editengine/defaults",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Change Default Values'))
                ->setIcon('fa-paint-brush')
                ->setHref($defaults_uri)
                ->setWorkflow(!$can_edit)
                ->setDisabled(!$can_edit));

        $reorder_uri = Url::to([
            "/transactions/editengine/reorder",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Change Field Order'))
                ->setIcon('fa-sort-alpha-asc')
                ->setHref($reorder_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        $lock_uri = Url::to([
            "/transactions/editengine/lock",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Lock / Hide Fields'))
                ->setIcon('fa-lock')
                ->setHref($lock_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        if ($engine->supportsSubtypes()) {
            $subtype_uri = Url::to([
                "/transactions/editengine/subtype",
                "engineKey" => $engine_key,
                "key" => $form_key,
            ]);

            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app",'Change Form Subtype'))
                    ->setIcon('fa-drivers-license-o')
                    ->setHref($subtype_uri)
                    ->setWorkflow(true)
                    ->setDisabled(!$can_edit));
        }

        $disable_uri = Url::to([
            "/transactions/editengine/disable",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        if ($config->getIsDisabled()) {
            $disable_name = \Yii::t("app",'Enable Form');
            $disable_icon = 'fa-check';
        } else {
            $disable_name = \Yii::t("app",'Disable Form');
            $disable_icon = 'fa-ban';
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName($disable_name)
                ->setIcon($disable_icon)
                ->setHref($disable_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        $defaultcreate_uri = Url::to([
            "/transactions/editengine/defaultcreate",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        if ($config->getIsDefault()) {
            $defaultcreate_name = \Yii::t("app",'Unmark as "Create" Form');
            $defaultcreate_icon = 'fa-minus';
        } else {
            $defaultcreate_name = \Yii::t("app",'Mark as "Create" Form');
            $defaultcreate_icon = 'fa-plus';
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName($defaultcreate_name)
                ->setIcon($defaultcreate_icon)
                ->setHref($defaultcreate_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        if ($config->getIsEdit()) {
            $isedit_name = \Yii::t("app",'Unmark as "Edit" Form');
            $isedit_icon = 'fa-minus';
        } else {
            $isedit_name = \Yii::t("app",'Mark as "Edit" Form');
            $isedit_icon = 'fa-plus';
        }

        $isedit_uri = Url::to([
            "/transactions/editengine/defaultedit",
            "engineKey" => $engine_key,
            "key" => $form_key,
        ]);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName($isedit_name)
                ->setIcon($isedit_icon)
                ->setHref($isedit_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        return $curtain;
    }

    /**
     * @param PhabricatorEditEngineConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    private function buildPropertyView(
        PhabricatorEditEngineConfiguration $config)
    {
        $viewer = $this->getViewer();

        $properties = (new PHUIPropertyListView())
            ->setUser($viewer);

        return $properties;
    }

    /**
     * @param PhabricatorEditEngineConfiguration $config
     * @return array
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildFieldList(PhabricatorEditEngineConfiguration $config)
    {
        $viewer = $this->getViewer();
        $engine = $config->getEngine();

        $fields = $engine->getFieldsForConfig($config);

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->setAction(null);

        foreach ($fields as $field) {
            $field->setIsPreview(true);

            $field->appendToForm($form);
        }

        $info = (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
            ->setErrors(
                array(
                    \Yii::t("app",'This is a preview of the current form configuration.'),
                ));

        $box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Form Preview'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        return array($info, $box);
    }

}
