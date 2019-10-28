<?php

namespace orangins\modules\policy\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use PhutilSafeHTML;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormPolicyControl;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\lib\view\form\PHUIFormInsetView;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\assets\JavelinPolicyRuleEditorBehaviorAsset;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorPolicyFavoritesSetting;
use PhutilClassMapQuery;
use PhutilJSONParserException;
use PhutilProxyException;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPolicyEditAction
 * @package orangins\modules\policy\actions
 * @author 陈妙威
 */
final class PhabricatorPolicyEditAction extends PhabricatorPolicyAction
{

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $object_phid = $request->getURIData('objectPHID');
        if ($object_phid) {
            $object = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($object_phid))
                ->executeOne();
            if (!$object) {
                return new Aphront404Response();
            }
        } else {
            $object_type = $request->getURIData('objectType');
            if (!$object_type) {
                $object_type = $request->getURIData('templateType');
            }

            $phid_types = PhabricatorPHIDType::getAllInstalledTypes($viewer);
            if (empty($phid_types[$object_type])) {
                return new Aphront404Response();
            }
            /** @var PhabricatorPHIDType $var */
            $var = $phid_types[$object_type];
            $object = $var->newObject();
            if (!$object) {
                return new Aphront404Response();
            }
        }

        $phid = $request->getURIData('phid');
        switch ($phid) {
            case AphrontFormPolicyControl::getSelectProjectKey():
                return $this->handleProjectRequest($request);
            case AphrontFormPolicyControl::getSelectCustomKey():
                $phid = null;
                break;
            default:
                break;
        }

        $action_options = array(
            PhabricatorPolicy::ACTION_ALLOW => \Yii::t("app",'Allow'),
            PhabricatorPolicy::ACTION_DENY => \Yii::t("app",'Deny'),
        );

        $rules = (new PhutilClassMapQuery())
            ->setUniqueMethod('getClassShortName')
            ->setAncestorClass(PhabricatorPolicyRule::class)
            ->execute();

        foreach ($rules as $key => $rule) {
            if (!$rule->canApplyToObject($object)) {
                unset($rules[$key]);
            }
        }

        $rules = msort($rules, 'getRuleOrder');

        $default_rule = array(
            'action' => head_key($action_options),
            'rule' => head_key($rules),
            'value' => null,
        );

        /** @var PhabricatorPolicy $policy */
        if ($phid) {
            $policies = PhabricatorPolicy::find()
                ->setViewer($viewer)
                ->withPHIDs(array($phid))
                ->execute();
            if (!$policies) {
                return new Aphront404Response();
            }
            $policy = head($policies);
        } else {
            $policy = (new PhabricatorPolicy())
                ->setRules(array($default_rule))
                ->setDefaultAction(PhabricatorPolicy::ACTION_DENY);
        }

        $root_id = JavelinHtml::generateUniqueNodeId();

        $default_action = $policy->getDefaultAction();
        $rule_data = $policy->getRules();

        $errors = array();
        if ($request->isFormPost()) {
            $data = $request->getStr('rules');
            try {
                $data = phutil_json_decode($data);
            } catch (PhutilJSONParserException $ex) {
                throw new PhutilProxyException(
                    \Yii::t("app",'Failed to JSON decode rule data!'),
                    $ex);
            }

            $rule_data = array();
            foreach ($data as $rule) {
                $action = ArrayHelper::getValue($rule, 'action');
                switch ($action) {
                    case 'allow':
                    case 'deny':
                        break;
                    default:
                        throw new Exception(\Yii::t("app","Invalid action '{0}'!", [$action]));
                }

                $rule_class = ArrayHelper::getValue($rule, 'rule');
                if (empty($rules[$rule_class])) {
                    throw new Exception(\Yii::t("app","Invalid rule class '{0}'!", [$rule_class]));
                }

                $rule_obj = $rules[$rule_class];

                $value = $rule_obj->getValueForStorage(ArrayHelper::getValue($rule, 'value'));

                $rule_data[] = array(
                    'action' => $action,
                    'rule' => $rule_class,
                    'value' => $value,
                );
            }

            // Filter out nonsense rules, like a "users" rule without any users
            // actually specified.
            $valid_rules = array();
            foreach ($rule_data as $rule) {
                $rule_class = $rule['rule'];
                if ($rules[$rule_class]->ruleHasEffect($rule['value'])) {
                    $valid_rules[] = $rule;
                }
            }

            if (!$valid_rules) {
                $errors[] = \Yii::t("app",'None of these policy rules have any effect.');
            }

            // NOTE: Policies are immutable once created, and we always create a new
            // policy here. If we didn't, we would need to lock this endpoint down,
            // as users could otherwise just go edit the policies of objects with
            // custom policies.

            if (!$errors) {
                $new_policy = new PhabricatorPolicy();
                $new_policy->setRules($valid_rules);
                $new_policy->setDefaultAction($request->getStr('default'));
                $new_policy->save();

                $data = array(
                    'phid' => $new_policy->getPHID(),
                    'info' => array(
                        'name' => $new_policy->getName(),
                        'full' => $new_policy->getName(),
                        'icon' => $new_policy->getIcon(),
                    ),
                );

                return (new AphrontAjaxResponse())->setContent($data);
            }
        }

        // Convert rule values to display format (for example, expanding PHIDs
        // into tokens).
        foreach ($rule_data as $key => $rule) {
            $rule_data[$key]['value'] = $rules[$rule['rule']]->getValueForDisplay(
                $viewer,
                $rule['value']);
        }

        $default_select = AphrontFormSelectControl::renderSelectTag(
            $default_action,
            $action_options,
            array(
                'name' => 'default',
                'class' => 'form-control w-25 d-inline'
            ));

        if ($errors) {
            $errors = (new PHUIInfoView())
                ->setErrors($errors);
        }

        $form = (new PHUIFormLayoutView())
            ->appendChild($errors)
            ->appendChild(
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'rules',
                        'sigil' => 'rules',
                    )))
            ->appendChild(JavelinHtml::phutil_tag_div("table-responsive", (new PHUIFormInsetView())
                ->setTitle(\Yii::t("app",'Rules'))
                ->setRightButton(
                    JavelinHtml::phutil_tag(
                        'a',
                        array(
                            'href' => '#',
                            'class' => 'btn btn-success btn-xs',
                            'sigil' => 'create-rule',
                            'mustcapture' => true,
                        ),
                        \Yii::t("app",'New Rule')))
                ->setDescription(\Yii::t("app",'These rules are processed in order.'))
                ->setContent( JavelinHtml::phutil_tag(
                    'table',
                    array(
                        'sigil' => 'rules',
                        'class' => 'table table-striped policy-rules-table',
                    ),
                    ''))))
            ->appendChild(JavelinHtml::phutil_tag("hr", ['class' => 'm-0']))
            ->appendChild(
                (new AphrontFormMarkupControl())
                    ->setLabel(\Yii::t("app",'If No Rules Match'))
                    ->addClass("mt-3")
                    ->setValue(new PhutilSafeHTML(\Yii::t("app",
                        '{0} all other users.', [
                            $default_select
                        ]))));

        $form = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $root_id,
            ),
            $form);

        $rule_options = mpull($rules, 'getRuleDescription');
        $type_map = mpull($rules, 'getValueControlType');
        $templates = mpull($rules, 'getValueControlTemplate');

        JavelinHtml::initBehavior(
            new JavelinPolicyRuleEditorBehaviorAsset(),
            array(
                'rootID' => $root_id,
                'actions' => $action_options,
                'rules' => $rule_options,
                'types' => $type_map,
                'templates' => $templates,
                'data' => $rule_data,
                'defaultRule' => $default_rule,
            ));

        $title = \Yii::t("app",'Custom Policy');

        $key = $request->getStr('capability');
        if ($key) {
            $capability = PhabricatorPolicyCapability::getCapabilityByKey($key);
            $title = \Yii::t("app",'Custom "{0}" Policy', [$capability->getCapabilityName()]);
        }

        $dialog = (new AphrontDialogView())
            ->setWidth(AphrontDialogView::WIDTH_FULL)
            ->setUser($viewer)
            ->setTitle($title)
            ->appendChild($form)
            ->addSubmitButton(\Yii::t("app",'Save Policy'))
            ->addCancelButton('#');

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

    /**
     * @param AphrontRequest $request
     * @return AphrontAjaxResponse|AphrontDialogView
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function handleProjectRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();

        $errors = array();
        $e_project = true;

        if ($request->isFormPost()) {
            $project_phids = $request->getArr('projectPHIDs');
            $project_phid = head($project_phids);

            $project = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($project_phid))
                ->executeOne();

            if ($project) {
                // Save this project as one of the user's most recently used projects,
                // so we'll show it by default in future menus.

                $favorites_key = PhabricatorPolicyFavoritesSetting::SETTINGKEY;
                $favorites = $viewer->getUserSetting($favorites_key);
                if (!is_array($favorites)) {
                    $favorites = array();
                }

                // Add this, or move it to the end of the list.
                unset($favorites[$project_phid]);
                $favorites[$project_phid] = true;

                $preferences = PhabricatorUserPreferences::loadUserPreferences($viewer);

                $editor = (new PhabricatorUserPreferencesEditor())
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnNoEffect(true)
                    ->setContinueOnMissingFields(true);

                $xactions = array();
                $xactions[] = $preferences->newTransaction($favorites_key, $favorites);
                $editor->applyTransactions($preferences, $xactions);

                $data = array(
                    'phid' => $project->getPHID(),
                    'info' => array(
                        'name' => $project->getName(),
                        'full' => $project->getName(),
                        'icon' => $project->getDisplayIconIcon(),
                    ),
                );

                return (new AphrontAjaxResponse())->setContent($data);
            } else {
                $errors[] = \Yii::t("app",'You must choose a project.');
                $e_project = \Yii::t("app",'Required');
            }
        }

        $project_datasource = (new PhabricatorProjectDatasource())
            ->setParameters(
                array(
                    'policy' => 1,
                ));

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setLabel(\Yii::t("app",'Members Of'))
                    ->setName('projectPHIDs')
                    ->setLimit(1)
                    ->setError($e_project)
                    ->setDatasource($project_datasource));

        return $this->newDialog()
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setErrors($errors)
            ->setTitle(\Yii::t("app",'Select Project'))
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app",'Done'))
            ->addCancelButton('#');
    }

}
