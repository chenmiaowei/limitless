<?php

namespace orangins\modules\herald\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\lib\view\form\control\AphrontFormStaticControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\capability\HeraldManageGlobalRulesCapability;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

/**
 * Class HeraldNewController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldNewController extends HeraldController
{

    /**
     * @return mixed|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $content_type_map = HeraldAdapter::getEnabledAdapterMap($viewer);
        $rule_type_map = HeraldRuleTypeConfig::getRuleTypeMap();

        $errors = array();

        $e_type = null;
        $e_rule = null;
        $e_object = null;

        $step = $request->getInt('step');
        if ($request->isFormPost()) {
            $content_type = $request->getStr('content_type');
            if (empty($content_type_map[$content_type])) {
                $errors[] = pht('You must choose a content type for this rule.');
                $e_type = pht('Required');
                $step = 0;
            }

            if (!$errors && $step > 1) {
                $rule_type = $request->getStr('rule_type');
                if (empty($rule_type_map[$rule_type])) {
                    $errors[] = pht('You must choose a rule type for this rule.');
                    $e_rule = pht('Required');
                    $step = 1;
                }
            }

            if (!$errors && $step >= 2) {
                $target_phid = null;
                $object_name = $request->getStr('objectName');
                $done = false;
                if ($rule_type != HeraldRuleTypeConfig::RULE_TYPE_OBJECT) {
                    $done = true;
                } else if (strlen($object_name)) {
                    $target_object = (new PhabricatorObjectQuery())
                        ->setViewer($viewer)
                        ->withNames(array($object_name))
                        ->executeOne();
                    if ($target_object) {
                        $can_edit = PhabricatorPolicyFilter::hasCapability(
                            $viewer,
                            $target_object,
                            PhabricatorPolicyCapability::CAN_EDIT);
                        if (!$can_edit) {
                            $errors[] = pht(
                                'You can not create a rule for that object, because you do ' .
                                'not have permission to edit it. You can only create rules ' .
                                'for objects you can edit.');
                            $e_object = pht('Not Editable');
                            $step = 2;
                        } else {
                            $adapter = HeraldAdapter::getAdapterForContentType($content_type);
                            if (!$adapter->canTriggerOnObject($target_object)) {
                                $errors[] = pht(
                                    'This object is not of an allowed type for the rule. ' .
                                    'Rules can only trigger on certain objects.');
                                $e_object = pht('Invalid');
                                $step = 2;
                            } else {
                                $target_phid = $target_object->getPHID();
                                $done = true;
                            }
                        }
                    } else {
                        $errors[] = pht('No object exists by that name.');
                        $e_object = pht('Invalid');
                        $step = 2;
                    }
                } else if ($step > 2) {
                    $errors[] = pht(
                        'You must choose an object to associate this rule with.');
                    $e_object = pht('Required');
                    $step = 2;
                }

                if (!$errors && $done) {
                    $params = array(
                        'content_type' => $content_type,
                        'rule_type' => $rule_type,
                        'targetPHID' => $target_phid,
                    );
                    $uri = $this->getApplicationURI('index/edit', $params);
                    return (new AphrontRedirectResponse())->setURI($uri);
                }
            }
        }

        $content_type = $request->getStr('content_type');
        $rule_type = $request->getStr('rule_type');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->setAction($this->getApplicationURI('index/new'));

        switch ($step) {
            case 0:
            default:
                $content_types = $this->renderContentTypeControl(
                    $content_type_map,
                    $e_type);

                $form
                    ->addHiddenInput('step', 1)
                    ->appendChild($content_types);

                $cancel_text = null;
                $cancel_uri = $this->getApplicationURI();
                $title = pht('Create Herald Rule');
                break;
            case 1:
                $rule_types = $this->renderRuleTypeControl(
                    $rule_type_map,
                    $e_rule);

                $form
                    ->addHiddenInput('content_type', $content_type)
                    ->addHiddenInput('step', 2)
                    ->appendChild($rule_types);

                $params = array(
                    'content_type' => $content_type,
                    'step' => '0',
                );

                $cancel_text = pht('Back');
                $cancel_uri = $this->getApplicationURI('index/new', $params);
                $title = pht('Create Herald Rule: %s',
                    idx($content_type_map, $content_type));
                break;
            case 2:
                $adapter = HeraldAdapter::getAdapterForContentType($content_type);
                $form
                    ->addHiddenInput('content_type', $content_type)
                    ->addHiddenInput('rule_type', $rule_type)
                    ->addHiddenInput('step', 3)
                    ->appendChild(
                        (new AphrontFormStaticControl())
                            ->setLabel(pht('Rule for'))
                            ->setValue(
                                phutil_tag(
                                    'strong',
                                    array(),
                                    idx($content_type_map, $content_type))))
                    ->appendChild(
                        (new AphrontFormStaticControl())
                            ->setLabel(pht('Rule Type'))
                            ->setValue(
                                phutil_tag(
                                    'strong',
                                    array(),
                                    idx($rule_type_map, $rule_type))))
                    ->appendRemarkupInstructions(
                        pht(
                            'Choose the object this rule will act on (for example, enter ' .
                            '`rX` to act on the `rX` repository, or `#project` to act on ' .
                            'a project).'))
                    ->appendRemarkupInstructions(
                        $adapter->explainValidTriggerObjects())
                    ->appendChild(
                        (new AphrontFormTextControl())
                            ->setName('objectName')
                            ->setError($e_object)
                            ->setValue($request->getStr('objectName'))
                            ->setLabel(pht('Object')));

                $params = array(
                    'content_type' => $content_type,
                    'rule_type' => $rule_type,
                    'step' => 1,
                );

                $cancel_text = pht('Back');
                $cancel_uri = $this->getApplicationURI('index/new', $params);
                $title = pht('Create Herald Rule: %s',
                    idx($content_type_map, $content_type));
                break;
        }

        $form
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(pht('Continue'))
                    ->addCancelButton($cancel_uri, $cancel_text));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $crumbs = $this
            ->buildApplicationCrumbs()
            ->addTextCrumb(pht('Create Rule'))
            ->setBorder(true);

        $view = (new PHUITwoColumnView())
            ->setFooter($form_box);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild(
                array(
                    $view,
                ));
    }

    /**
     * @param array $content_type_map
     * @param $e_type
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function renderContentTypeControl(array $content_type_map, $e_type)
    {
        $request = $this->getRequest();

        $radio = (new AphrontFormRadioButtonControl())
            ->setLabel(pht('New Rule for'))
            ->setName('content_type')
            ->setValue($request->getStr('content_type'))
            ->setError($e_type);

        foreach ($content_type_map as $value => $name) {
            $adapter = HeraldAdapter::getAdapterForContentType($value);
            $radio->addButton(
                $value,
                $name,
                phutil_escape_html_newlines($adapter->getAdapterContentDescription()));
        }

        return $radio;
    }


    /**
     * @param array $rule_type_map
     * @param $e_rule
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderRuleTypeControl(array $rule_type_map, $e_rule)
    {
        $request = $this->getRequest();

        // Reorder array to put less powerful rules first.
        $rule_type_map = array_select_keys(
                $rule_type_map,
                array(
                    HeraldRuleTypeConfig::RULE_TYPE_PERSONAL,
                    HeraldRuleTypeConfig::RULE_TYPE_OBJECT,
                    HeraldRuleTypeConfig::RULE_TYPE_GLOBAL,
                )) + $rule_type_map;

        list($can_global, $global_link) = $this->explainApplicationCapability(
            HeraldManageGlobalRulesCapability::CAPABILITY,
            pht('You have permission to create and manage global rules.'),
            pht('You do not have permission to create or manage global rules.'));

        $captions = array(
            HeraldRuleTypeConfig::RULE_TYPE_PERSONAL =>
                pht(
                    'Personal rules notify you about events. You own them, but they can ' .
                    'only affect you. Personal rules only trigger for objects you have ' .
                    'permission to see.'),
            HeraldRuleTypeConfig::RULE_TYPE_OBJECT =>
                pht(
                    'Object rules notify anyone about events. They are bound to an ' .
                    'object (like a repository) and can only act on that object. You ' .
                    'must be able to edit an object to create object rules for it. ' .
                    'Other users who can edit the object can edit its rules.'),
            HeraldRuleTypeConfig::RULE_TYPE_GLOBAL =>
                array(
                    pht(
                        'Global rules notify anyone about events. Global rules can ' .
                        'bypass access control policies and act on any object.'),
                    $global_link,
                ),
        );

        $radio = (new AphrontFormRadioButtonControl())
            ->setLabel(pht('Rule Type'))
            ->setName('rule_type')
            ->setValue($request->getStr('rule_type'))
            ->setError($e_rule);

        $adapter = HeraldAdapter::getAdapterForContentType(
            $request->getStr('content_type'));

        foreach ($rule_type_map as $value => $name) {
            $caption = idx($captions, $value);
            $disabled = ($value == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL) &&
                (!$can_global);

            if (!$adapter->supportsRuleType($value)) {
                $disabled = true;
                $caption = array(
                    $caption,
                    "\n\n",
                    phutil_tag(
                        'em',
                        array(),
                        pht(
                            'This rule type is not supported by the selected content type.')),
                );
            }

            $radio->addButton(
                $value,
                $name,
                phutil_escape_html_newlines($caption),
                $disabled ? 'disabled' : null,
                $disabled);
        }

        return $radio;
    }

}
