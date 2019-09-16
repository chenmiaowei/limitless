<?php

namespace orangins\modules\policy\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\codex\PhabricatorPolicyCodex;
use orangins\modules\policy\codex\PhabricatorPolicyCodexInterface;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\phid\PhabricatorPolicyPHIDTypePolicy;
use orangins\lib\view\phui\PHUIIconView;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "policy".
 *
 * @property int $id
 * @property string $phid
 * @property string $rules
 * @property string $default_action
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorPolicy extends ActiveRecordPHID
{
    /**
     *
     */
    const ACTION_ALLOW = 'allow';
    /**
     *
     */
    const ACTION_DENY = 'deny';

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $shortName;
    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $workflow;
    /**
     * @var
     */
    private $icon;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'policy';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rules', 'default_action'], 'required'],
            [['rules'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid'], 'string', 'max' => 64],
            [['default_action'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'rules' => Yii::t('app', 'Rules'),
            'default_action' => Yii::t('app', 'Default Action'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorPolicyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorPolicyQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorPolicyPHIDTypePolicy::class;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        if (!$this->name) {
            return \Yii::t("app", 'Custom Policy');
        }
        return $this->name;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortName()
    {
        if ($this->shortName) {
            return $this->shortName;
        }
        return $this->getName();
    }


    /**
     * @param mixed $shortName
     * @return self
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param mixed $href
     * @return self
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param mixed $workflow
     * @return self
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        if ($this->icon) {
            return $this->icon;
        }

        switch ($this->getType()) {
            case PhabricatorPolicyType::TYPE_GLOBAL:
                static $map = array(
                    PhabricatorPolicies::POLICY_PUBLIC => 'fa-globe',
                    PhabricatorPolicies::POLICY_USER => 'fa-users',
                    PhabricatorPolicies::POLICY_ADMIN => 'fa-eye',
                    PhabricatorPolicies::POLICY_NOONE => 'fa-ban',
                );
                return ArrayHelper::getValue($map, $this->getPHID(), 'fa-question-circle');
            case PhabricatorPolicyType::TYPE_USER:
                return 'fa-user';
            case PhabricatorPolicyType::TYPE_PROJECT:
                return 'fa-briefcase';
            case PhabricatorPolicyType::TYPE_CUSTOM:
            case PhabricatorPolicyType::TYPE_MASKED:
                return 'fa-certificate';
            default:
                return 'fa-question-circle';
        }
    }

    /**
     * @param mixed $icon
     * @return self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param string $phid
     * @return self
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getRules($key = null, $default = null)
    {
        $phutil_json_decode = $this->rules === null ? [] : phutil_json_decode($this->rules);
        if ($key === null) {
            return $phutil_json_decode;
        } else {
            return ArrayHelper::getValue($phutil_json_decode, $key, $default);
        }
    }

//    /**
//     * @param $key
//     * @param $value
//     * @return PhabricatorPolicy
//     * @throws \Exception
//     * @author 陈妙威
//     */
//    public function setRules($key, $value)
//    {
//        $parameter = $this->getRules();
//        $parameter[$key] = $value;
//        $this->rules = phutil_json_encode($parameter);
//        return $this;
//    }

    /**
     * @param $value
     * @return PhabricatorPolicy
     * @throws \Exception
     * @author 陈妙威
     */
    public function setRules($value)
    {
        $this->rules = phutil_json_encode($value);
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->default_action;
    }

    /**
     * @param string $default_action
     * @return self
     */
    public function setDefaultAction($default_action)
    {
        $this->default_action = $default_action;
        return $this;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getSortKey()
    {
        return sprintf(
            '%02d%s',
            PhabricatorPolicyType::getPolicyTypeOrder($this->getType()),
            $this->getSortName());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getSortName()
    {
        if ($this->getType() == PhabricatorPolicyType::TYPE_GLOBAL) {
            static $map = array(
                PhabricatorPolicies::POLICY_PUBLIC => 0,
                PhabricatorPolicies::POLICY_USER => 1,
                PhabricatorPolicies::POLICY_ADMIN => 2,
                PhabricatorPolicies::POLICY_NOONE => 3,
            );
            return ArrayHelper::getValue($map, $this->getPHID());
        }
        return $this->getName();
    }

    /**
     * @param $policy_identifier
     * @param PhabricatorObjectHandle|null $handle
     * @return mixed|null
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function newFromPolicyAndHandle(
        $policy_identifier,
        PhabricatorObjectHandle $handle = null)
    {

        $is_global = PhabricatorPolicyQuery::isGlobalPolicy($policy_identifier);
        if ($is_global) {
            return PhabricatorPolicyQuery::getGlobalPolicy($policy_identifier);
        }

        $policy = PhabricatorPolicyQuery::getObjectPolicy($policy_identifier);
        if ($policy) {
            return $policy;
        }

        if (!$handle) {
            throw new Exception(
                \Yii::t("app",
                    "Policy identifier is an object PHID ('%s'), but no object handle " .
                    "was provided. A handle must be provided for object policies.",
                    $policy_identifier));
        }

        $handle_phid = $handle->getPHID();
        if ($policy_identifier != $handle_phid) {
            throw new Exception(
                \Yii::t("app",
                    "Policy identifier is an object PHID ('%s'), but the provided " .
                    "handle has a different PHID ('%s'). The handle must correspond " .
                    "to the policy identifier.",
                    $policy_identifier,
                    $handle_phid));
        }


        $policy = (new PhabricatorPolicy())
            ->setPHID($policy_identifier)
            ->setHref($handle->getURI());

        $phid_type = PhabricatorPHID::phid_get_type($policy_identifier);
        switch ($phid_type) {
//            case PhabricatorProjectProjectPHIDType::TYPECONST:
//                $policy->setType(PhabricatorPolicyType::TYPE_PROJECT);
//                $policy->setName($handle->getName());
//                break;
            case PhabricatorPeopleUserPHIDType::TYPECONST:
                $policy->setType(PhabricatorPolicyType::TYPE_USER);
                $policy->setName($handle->getFullName());
                break;
            case PhabricatorPolicyPHIDTypePolicy::TYPECONST:
                // TODO: This creates a weird handle-based version of a rule policy.
                // It behaves correctly, but can't be applied since it doesn't have
                // any rules. It is used to render transactions, and might need some
                // cleanup.
                break;
            default:
                $policy->setType(PhabricatorPolicyType::TYPE_MASKED);
                $policy->setName($handle->getFullName());
                break;
        }

        $policy->makeEphemeral();

        return $policy;
    }

    /**
     * @param bool $icon
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderDescription($icon = false)
    {
        $img = null;
        if ($icon) {
            $img = (new PHUIIconView())
                ->setIcon($this->getIcon());
        }

        if ($this->getHref()) {
            $desc = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $this->getHref(),
                    'class' => 'policy-link',
                    'sigil' => $this->getWorkflow() ? 'workflow' : null,
                ),
                array(
                    $img,
                    $this->getName(),
                ));
        } else {
            if ($img) {
                $desc = array($img, $this->getName());
            } else {
                $desc = $this->getName();
            }
        }

        switch ($this->getType()) {
            case PhabricatorPolicyType::TYPE_PROJECT:
                return Yii::t("app", '{0} (Project)', [$desc]);
            case PhabricatorPolicyType::TYPE_CUSTOM:
                return $desc;
            case PhabricatorPolicyType::TYPE_MASKED:
                return Yii::t("app",
                    '{0} (You do not have permission to view policy details.)',
                    [$desc]);
            default:
                return $desc;
        }
    }


    /**
     * @param PhabricatorPolicyInterface $object
     * @param PhabricatorUser $viewer
     * @param $capability
     * @param $active_only
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getSpecialRules(
        PhabricatorPolicyInterface $object,
        PhabricatorUser $viewer,
        $capability,
        $active_only)
    {

        $exceptions = array();
        if ($object instanceof PhabricatorPolicyCodexInterface) {
            $newFromObject = PhabricatorPolicyCodex::newFromObject($object, $viewer);
            $codex = $newFromObject
                ->setCapability($capability);
            $rules = $codex->getPolicySpecialRuleDescriptions();
            foreach ($rules as $rule) {
                $is_active = $rule->getIsActive();
                if ($is_active) {
                    $rule_capabilities = $rule->getCapabilities();
                    if ($rule_capabilities) {
                        if (!in_array($capability, $rule_capabilities)) {
                            $is_active = false;
                        }
                    }
                }

                if (!$is_active && $active_only) {
                    continue;
                }

                $description = $rule->getDescription();

                if (!$is_active) {
                    $description = JavelinHtml::phutil_tag('span', array(
                        'class' => 'phui-policy-section-view-inactive-rule',
                    ), $description);
                }

                $exceptions[] = $description;
            }
        }

        if (!$exceptions) {
            if (method_exists($object, 'describeAutomaticCapability')) {
                $exceptions = (array)$object->describeAutomaticCapability($capability);
                $exceptions = array_filter($exceptions);
            }
        }

        return $exceptions;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $policy
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function getPolicyExplanation(
        PhabricatorUser $viewer,
        $policy)
    {

        $rule = PhabricatorPolicyQuery::getObjectPolicyRule($policy);
        if ($rule) {
            return $rule->getPolicyExplanation();
        }

        switch ($policy) {
            case PhabricatorPolicies::POLICY_PUBLIC:
                return \Yii::t("app",
                    'This object is public and can be viewed by anyone, even if they ' .
                    'do not have a Phabricator account.');
            case PhabricatorPolicies::POLICY_USER:
                return \Yii::t("app", 'Logged in users can take this action.');
            case PhabricatorPolicies::POLICY_ADMIN:
                return \Yii::t("app", 'Administrators can take this action.');
            case PhabricatorPolicies::POLICY_NOONE:
                return \Yii::t("app", 'By default, no one can take this action.');
            default:
                $handle = (new PhabricatorHandleQuery())
                    ->setViewer($viewer)
                    ->withPHIDs(array($policy))
                    ->executeOne();

                $type = PhabricatorPHID::phid_get_type($policy);
                if ($type == PhabricatorPeopleUserPHIDType::TYPECONST) {
                    return \Yii::t("app",
                        '%s can take this action.',
                        $handle->getFullName());
                } else if ($type == PhabricatorPolicyPHIDTypePolicy::TYPECONST) {
                    return \Yii::t("app",
                        'This object has a custom policy controlling who can take this ' .
                        'action.');
                } else {
                    return \Yii::t("app",
                        'This object has an unknown or invalid policy setting ("%s").',
                        $policy);
                }
        }
    }


    /**
     * Return `true` if this policy is stronger (more restrictive) than some
     * other policy.
     *
     * Because policies are complicated, determining which policies are
     * "stronger" is not trivial. This method uses a very coarse working
     * definition of policy strength which is cheap to compute, unambiguous,
     * and intuitive in the common cases.
     *
     * This method returns `true` if the //class// of this policy is stronger
     * than the other policy, even if the policies are (or might be) the same in
     * practice. For example, "Members of Project X" is considered a stronger
     * policy than "All Users", even though "Project X" might (in some rare
     * cases) contain every user.
     *
     * Generally, the ordering here is:
     *
     *   - Public
     *   - All Users
     *   - (Everything Else)
     *   - No One
     *
     * In the "everything else" bucket, we can't make any broad claims about
     * which policy is stronger (and we especially can't make those claims
     * cheaply).
     *
     * Even if we fully evaluated each policy, the two policies might be
     * "Members of X" and "Members of Y", each of which permits access to some
     * set of unique users. In this case, neither is strictly stronger than
     * the other.
     *
     * @param PhabricatorPolicy Other policy.
     * @return bool `true` if this policy is more restrictive than the other
     *  policy.
     */
    public function isStrongerThan(PhabricatorPolicy $other) {
        $this_policy = $this->getPHID();
        $other_policy = $other->getPHID();

        $strengths = array(
            PhabricatorPolicies::POLICY_PUBLIC => -2,
            PhabricatorPolicies::POLICY_USER => -1,
            // (Default policies have strength 0.)
            PhabricatorPolicies::POLICY_NOONE => 1,
        );

        $this_strength = ArrayHelper::getValue($strengths, $this->getPHID(), 0);
        $other_strength = ArrayHelper::getValue($strengths, $other->getPHID(), 0);

        return ($this_strength > $other_strength);
    }
}
