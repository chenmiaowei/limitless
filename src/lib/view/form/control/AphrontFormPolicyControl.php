<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\assets\JavelinPolicyControlBehaviorAsset;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use PhutilInvalidStateException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class AphrontFormPolicyControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormPolicyControl extends AphrontFormControl
{

    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $capability;
    /**
     * @var PhabricatorPolicy[]
     */
    private $policies;
    /**
     * @var
     */
    private $spacePHID;
    /**
     * @var
     */
    private $templatePHIDType;
    /**
     * @var
     */
    private $templateObject;

    /**
     * @param PhabricatorPolicyInterface $object
     * @return $this
     * @author 陈妙威
     */
    public function setPolicyObject(PhabricatorPolicyInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param array $policies
     * @return $this
     * @author 陈妙威
     */
    public function setPolicies(array $policies)
    {
        assert_instances_of($policies, PhabricatorPolicy::class);
        $this->policies = $policies;
        return $this;
    }

    /**
     * @param $space_phid
     * @return $this
     * @author 陈妙威
     */
    public function setSpacePHID($space_phid)
    {
        $this->spacePHID = $space_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSpacePHID()
    {
        return $this->spacePHID;
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setTemplatePHIDType($type)
    {
        $this->templatePHIDType = $type;
        return $this;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setTemplateObject($object)
    {
        $this->templateObject = $object;
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSerializedValue()
    {
        return json_encode(array(
            $this->getValue(),
            $this->getSpacePHID(),
        ));
    }

    /**
     * @param $value
     * @return $this|AphrontFormControl
     * @author 陈妙威
     */
    public function readSerializedValue($value)
    {
        $decoded = phutil_json_decode($value);
        $policy_value = $decoded[0];
        $space_phid = $decoded[1];
        $this->setValue($policy_value);
        $this->setSpacePHID($space_phid);
        return $this;
    }

    /**
     * @param array $dictionary
     * @return AphrontFormControl
     * @author 陈妙威
     */
    public function readValueFromDictionary(array $dictionary)
    {
        // TODO: This is a little hacky but will only get us into trouble if we
        // have multiple view policy controls in multiple paged form views on the
        // same page, which seems unlikely.
        $this->setSpacePHID(ArrayHelper::getValue($dictionary, 'spacePHID'));

        return parent::readValueFromDictionary($dictionary);
    }

    /**
     * @param AphrontRequest $request
     * @return AphrontFormControl
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        // See note in readValueFromDictionary().
        $this->setSpacePHID($request->getStr('spacePHID'));

        return parent::readValueFromRequest($request);
    }

    /**
     * @param $capability
     * @return $this
     * @author 陈妙威
     */
    public function setCapability($capability)
    {
        $this->capability = $capability;

        $labels = array(
            PhabricatorPolicyCapability::CAN_VIEW => \Yii::t("app", 'Visible To'),
            PhabricatorPolicyCapability::CAN_EDIT => \Yii::t("app", 'Editable By'),
            PhabricatorPolicyCapability::CAN_JOIN => \Yii::t("app", 'Joinable By'),
        );

        if (isset($labels[$capability])) {
            $label = $labels[$capability];
        } else {
            $capobj = PhabricatorPolicyCapability::getCapabilityByKey($capability);
            if ($capobj) {
                $label = $capobj->getCapabilityName();
            } else {
                $label = \Yii::t("app", 'Capability "{0}"', [
                    $capability
                ]);
            }
        }

        $this->setLabel($label);

        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-policy';
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getOptions()
    {
        $capability = $this->capability;
        $policies = $this->policies;
        $viewer = $this->getUser();

        // Check if we're missing the policy for the current control value. This
        // is unusual, but can occur if the user is submitting a form and selected
        // an unusual project as a policy but the change has not been saved yet.
        $policy_map = mpull($policies, null, 'getPHID');
        $value = $this->getValue();
        if ($value && empty($policy_map[$value])) {
            $handle = (new PhabricatorHandleQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($value))
                ->executeOne();
            if ($handle->isComplete()) {
                $policies[] = PhabricatorPolicy::newFromPolicyAndHandle(
                    $value,
                    $handle);
            }
        }

        // Exclude object policies which don't make sense here. This primarily
        // filters object policies associated from template capabilities (like
        // "Default Task View Policy" being set to "Task Author") so they aren't
        // made available on non-template capabilities (like "Can Bulk Edit").
        foreach ($policies as $key => $policy) {
            if ($policy->getType() != PhabricatorPolicyType::TYPE_OBJECT) {
                continue;
            }

            $rule = PhabricatorPolicyQuery::getObjectPolicyRule($policy->getPHID());
            if (!$rule) {
                continue;
            }

            $target = nonempty($this->templateObject, $this->object);
            if (!$rule->canApplyToObject($target)) {
                unset($policies[$key]);
                continue;
            }
        }

        $options = array();
        foreach ($policies as $policy) {
            if ($policy->getPHID() == PhabricatorPolicies::POLICY_PUBLIC) {
                // Never expose "Public" for capabilities which don't support it.
                $capobj = PhabricatorPolicyCapability::getCapabilityByKey($capability);
                if (!$capobj || !$capobj->shouldAllowPublicPolicySetting()) {
                    continue;
                }
            }

            $options[$policy->getType()][$policy->getPHID()] = array(
                'name' => $policy->getName(),
                'full' => $policy->getName(),
                'icon' => $policy->getIcon(),
                'sort' => phutil_utf8_strtolower($policy->getName()),
            );
        }

//        $type_project = PhabricatorPolicyType::TYPE_PROJECT;

        // Make sure we have a "Projects" group before we adjust it.
//        if (empty($options[$type_project])) {
//            $options[$type_project] = array();
//        }
//
//        $options[$type_project] = isort($options[$type_project], 'sort');
//
//        $placeholder = (new PhabricatorPolicy())
//            ->setName(\Yii::t("app", 'Other Project...'))
//            ->setIcon('fa-search');
//
//        $options[$type_project][$this->getSelectProjectKey()] = array(
//            'name' => $placeholder->getName(),
//            'full' => $placeholder->getName(),
//            'icon' => $placeholder->getIcon(),
//        );

        // If we were passed several custom policy options, throw away the ones
        // which aren't the value for this capability. For example, an object might
        // have a custom view policy and a custom edit policy. When we render
        // the selector for "Can View", we don't want to show the "Can Edit"
        // custom policy -- if we did, the menu would look like this:
        //
        //   Custom
        //     Custom Policy
        //     Custom Policy
        //
        // ...where one is the "view" custom policy, and one is the "edit" custom
        // policy.

        $type_custom = PhabricatorPolicyType::TYPE_CUSTOM;
        if (!empty($options[$type_custom])) {
            $options[$type_custom] = array_select_keys(
                $options[$type_custom],
                array($this->getValue()));
        }

        // If there aren't any custom policies, add a placeholder policy so we
        // render a menu item. This allows the user to switch to a custom policy.

        if (empty($options[$type_custom])) {
            $placeholder = new PhabricatorPolicy();
            $placeholder->setName(\Yii::t("app", 'Custom Policy...'));
            $options[$type_custom][$this->getSelectCustomKey()] = array(
                'name' => $placeholder->getName(),
                'full' => $placeholder->getName(),
                'icon' => $placeholder->getIcon(),
            );
        }

        $options = array_select_keys(
            $options,
            array(
                PhabricatorPolicyType::TYPE_GLOBAL,
                PhabricatorPolicyType::TYPE_OBJECT,
                PhabricatorPolicyType::TYPE_USER,
                PhabricatorPolicyType::TYPE_CUSTOM,
                PhabricatorPolicyType::TYPE_PROJECT,
            ));

        return $options;
    }

    /**
     * @return mixed|string
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function renderInput()
    {
        if (!$this->object) {
            throw new PhutilInvalidStateException('setPolicyObject');
        }
        if (!$this->capability) {
            throw new PhutilInvalidStateException('setCapability');
        }

        $policy = $this->object->getPolicy($this->capability);
        if (!$policy) {
            // TODO: Make this configurable.
            $policy = PhabricatorPolicies::POLICY_USER;
        }

        if (!$this->getValue()) {
            $this->setValue($policy);
        }

        $control_id = JavelinHtml::generateUniqueNodeId();
        $input_id = JavelinHtml::generateUniqueNodeId();

        $caret = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'caret',
            ));

        $input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'id' => $input_id,
                'name' => $this->getName(),
                'value' => $this->getValue(),
            ));

        $options = $this->getOptions();

        $order = array();
        $labels = array();
        foreach ($options as $key => $values) {
            $order[$key] = array_keys($values);
            $labels[$key] = PhabricatorPolicyType::getPolicyTypeName($key);
        }

        $flat_options = array_mergev($options);

        $icons = array();
        foreach (igroup($flat_options, 'icon') as $icon => $ignored) {
            $icons[$icon] = (new PHUIIconView())
                ->addClass("pr-2")
                ->setIcon($icon);
        }

        $params = [];
        if ($this->templatePHIDType) {
            $params['templateType'] = $this->templatePHIDType;
        } else {
            $object_phid = $this->object->getPHID();
            if ($object_phid) {
                $params['objectPHID'] = $object_phid;
            } else {
                $object_type = PhabricatorPHID::phid_get_type($this->object->generatePHID());
                $params['objectType'] = $object_type;
            }
        }

        JavelinHtml::initBehavior(
            new JavelinPolicyControlBehaviorAsset(),
            array(
                'controlID' => $control_id,
                'inputID' => $input_id,
                'options' => $flat_options,
                'groups' => array_keys($options),
                'order' => $order,
                'labels' => $labels,
                'value' => $this->getValue(),
                'capability' => $this->capability,
                'editURI' => Url::to(ArrayHelper::merge(['/policy/index/edit'], $params)),
                'customKey' => $this->getSelectCustomKey(),
                'projectKey' => $this->getSelectProjectKey(),
                'disabled' => $this->getDisabled(),
            ));

        $selected = ArrayHelper::getValue($flat_options, $this->getValue(), array());
        $selected_icon = ArrayHelper::getValue($selected, 'icon');
        $selected_name = ArrayHelper::getValue($selected, 'name');

        $spaces_control = $this->buildSpacesControl();

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'form-control',
            ),
            array(
                $spaces_control,
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'class' => 'd-block dropdown text-grey-800 has-icon has-text policy-control',
                        'href' => '#',
                        'mustcapture' => true,
                        'sigil' => 'policy-control',
                        'id' => $control_id,
                    ),
                    array(
                        $caret,
                        JavelinHtml::phutil_tag(
                            'span',
                            array(
                                'sigil' => 'policy-label',
                                'class' => 'phui-button-text',
                            ),
                            array(
                                ArrayHelper::getValue($icons, $selected_icon),
                                $selected_name,
                            )),
                    )),
                $input,
            ));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public static function getSelectCustomKey()
    {
        return 'select:custom';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public static function getSelectProjectKey()
    {
        return 'select:project';
    }

    /**
     * @return mixed|null
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildSpacesControl()
    {
        if ($this->capability != PhabricatorPolicyCapability::CAN_VIEW) {
            return null;
        }

        if (!($this->object instanceof PhabricatorSpacesInterface)) {
            return null;
        }

        $viewer = $this->getUser();
        if (!PhabricatorSpacesNamespaceQuery::getViewerSpacesExist($viewer)) {
            return null;
        }

        $space_phid = $this->getSpacePHID();
        if ($space_phid === null) {
            $space_phid = $viewer->getDefaultSpacePHID();
        }

        $select = AphrontFormSelectControl::renderSelectTag(
            $space_phid,
            PhabricatorSpacesNamespaceQuery::getSpaceOptionsForViewer(
                $viewer,
                $space_phid),
            array(
                'disabled' => ($this->getDisabled() ? 'disabled' : null),
                'name' => 'spacePHID',
                'class' => 'aphront-space-select-control-knob',
            ));

        return $select;
    }

}
