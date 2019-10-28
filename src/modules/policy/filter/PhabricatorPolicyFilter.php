<?php

namespace orangins\modules\policy\filter;

use orangins\lib\OranginsObject;
use orangins\lib\db\ActiveRecord;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\interfaces\PhabricatorExtendedPolicyInterface;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\spaces\interfaces\PhabricatorSpacesInterface;
use PhutilInvalidStateException;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\exception\PhabricatorPolicyException;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use orangins\modules\policy\phid\PhabricatorPolicyPHIDTypePolicy;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPolicyFilter
 * @package orangins\modules\policy\filter
 * @author 陈妙威
 */
final class PhabricatorPolicyFilter extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var
     */
    private $objects;
    /**
     * @var
     */
    private $capabilities;
    /**
     * @var
     */
    private $raisePolicyExceptions;
    /**
     * @var
     */
    private $userProjects;
    /**
     * @var array
     */
    private $customPolicies = array();
    /**
     * @var array
     */
    private $objectPolicies = array();
    /**
     * @var
     */
    private $forcedPolicy;

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    public static function mustRetainCapability(
        PhabricatorUser $user,
        PhabricatorPolicyInterface $object,
        $capability)
    {

        if (!self::hasCapability($user, $object, $capability)) {
            throw new Exception(
                Yii::t("app",
                    "You can not make that edit, because it would remove your ability " .
                    "to '%s' the object.",
                    $capability));
        }
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    public static function requireCapability(
        PhabricatorUser $user,
        PhabricatorPolicyInterface $object,
        $capability)
    {
        $filter = (new PhabricatorPolicyFilter())
            ->setViewer($user)
            ->requireCapabilities(array($capability))
            ->raisePolicyExceptions(true)
            ->apply(array($object));
    }

    /**
     * Perform a capability check, acting as though an object had a specific
     * policy. This is primarily used to check if a policy is valid (for example,
     * to prevent users from editing away their ability to edit an object).
     *
     * Specifically, a check like this:
     *
     *   PhabricatorPolicyFilter::requireCapabilityWithForcedPolicy(
     *     $viewer,
     *     $object,
     *     PhabricatorPolicyCapability::CAN_EDIT,
     *     $potential_new_policy);
     *
     * ...will throw a @{class:PhabricatorPolicyException} if the new policy would
     * remove the user's ability to edit the object.
     *
     * @param PhabricatorUser $viewer
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @param $forced_policy
     * @return void
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    public static function requireCapabilityWithForcedPolicy(
        PhabricatorUser $viewer,
        PhabricatorPolicyInterface $object,
        $capability,
        $forced_policy)
    {

        (new PhabricatorPolicyFilter())
            ->setViewer($viewer)
            ->requireCapabilities(array($capability))
            ->raisePolicyExceptions(true)
            ->forcePolicy($forced_policy)
            ->apply(array($object));
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function hasCapability(
        PhabricatorUser $user,
        PhabricatorPolicyInterface $object,
        $capability)
    {

        $filter = new PhabricatorPolicyFilter();
        $filter->setViewer($user);
        $filter->requireCapabilities(array($capability));
        $result = $filter->apply(array($object));

        return (count($result) == 1);
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorPolicyInterface|ActiveRecord $object
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function canInteract(
        PhabricatorUser $user,
        PhabricatorPolicyInterface $object)
    {

        $capabilities = $object->getCapabilities();
        $capabilities = array_fuse($capabilities);

        $can_interact = PhabricatorPolicyCapability::CAN_INTERACT;
        $can_view = PhabricatorPolicyCapability::CAN_VIEW;

        $require = array();

        // If the object doesn't support a separate "Interact" capability, we
        // only use the "View" capability: for most objects, you can interact
        // with them if you can see them.
        $require[] = $can_view;

        if (isset($capabilities[$can_interact])) {
            $require[] = $can_interact;
        }

        foreach ($require as $capability) {
            if (!self::hasCapability($user, $object, $capability)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $user)
    {
        $this->viewer = $user;
        return $this;
    }

    /**
     * @param array $capabilities
     * @return $this
     * @author 陈妙威
     */
    public function requireCapabilities(array $capabilities)
    {
        $this->capabilities = $capabilities;
        return $this;
    }

    /**
     * @param $raise
     * @return $this
     * @author 陈妙威
     */
    public function raisePolicyExceptions($raise)
    {
        $this->raisePolicyExceptions = $raise;
        return $this;
    }

    /**
     * @param $forced_policy
     * @return $this
     * @author 陈妙威
     */
    public function forcePolicy($forced_policy)
    {
        $this->forcedPolicy = $forced_policy;
        return $this;
    }

    /**
     * @param array $objects
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @throws \Exception
     */
    public function apply(array $objects)
    {
        assert_instances_of($objects, PhabricatorPolicyInterface::class);

        $viewer = $this->viewer;
        $capabilities = $this->capabilities;

        if (!$viewer || !$capabilities) {
            throw new PhutilInvalidStateException('setViewer', 'requireCapabilities');
        }

        // If the viewer is omnipotent, short circuit all the checks and just
        // return the input unmodified. This is an optimization; we know the
        // result already.
        if ($viewer->isOmnipotent()) {
            return $objects;
        }

        // Before doing any actual object checks, make sure the viewer can see
        // the applications that these objects belong to. This is normally enforced
        // in the Query layer before we reach object filtering, but execution
        // sometimes reaches policy filtering without running application checks.
        $objects = $this->applyApplicationChecks($objects);

        $filtered = array();
        $viewer_phid = $viewer->getPHID();

        if (empty($this->userProjects[$viewer_phid])) {
            $this->userProjects[$viewer_phid] = array();
        }

        $need_policies = array();
        $need_objpolicies = array();
        foreach ($objects as $key => $object) {
            $object_capabilities = $object->getCapabilities();
            foreach ($capabilities as $capability) {
                if (!in_array($capability, $object_capabilities)) {
                    throw new Exception(
                        Yii::t("app",
                            "Testing for capability '{0}' on an object which does " .
                            "not have that capability!", [
                                $capability
                            ]));
                }

                $policy = $this->getObjectPolicy($object, $capability);

                if (PhabricatorPolicyQuery::isObjectPolicy($policy)) {
                    $need_objpolicies[$policy][] = $object;
                    continue;
                }

                $type = PhabricatorPHID::phid_get_type($policy);
                if ($type == PhabricatorPolicyPHIDTypePolicy::TYPECONST) {
                    $need_policies[$policy][] = $object;
                    continue;
                }
            }
        }

        if ($need_objpolicies) {
            $this->loadObjectPolicies($need_objpolicies);
        }

        if ($need_policies) {
            $this->loadCustomPolicies($need_policies);
        }

        foreach ($objects as $key => $object) {
            foreach ($capabilities as $capability) {
                if (!$this->checkCapability($object, $capability)) {
                    // If we're missing any capability, move on to the next object.
                    continue 2;
                }
            }

            // If we make it here, we have all of the required capabilities.
            $filtered[$key] = $object;
        }

        // If we survived the primary checks, apply extended checks to objects
        // with extended policies.
        $results = array();
        $extended = array();
        foreach ($filtered as $key => $object) {
            if ($object instanceof PhabricatorExtendedPolicyInterface) {
                $extended[$key] = $object;
            } else {
                $results[$key] = $object;
            }
        }

        if ($extended) {
            $results += $this->applyExtendedPolicyChecks($extended);
            // Put results back in the original order.
            $results = array_select_keys($results, array_keys($filtered));
        }

        return $results;
    }

    /**
     * @param PhabricatorExtendedPolicyInterface[] $extended_objects
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function applyExtendedPolicyChecks(array $extended_objects)
    {
        $viewer = $this->viewer;
        $filter_capabilities = $this->capabilities;

        // Iterate over the objects we need to filter and pull all the nonempty
        // policies into a flat, structured list.
        $all_structs = array();
        foreach ($extended_objects as $key => $extended_object) {
            foreach ($filter_capabilities as $extended_capability) {
                $extended_policies = $extended_object->getExtendedPolicy(
                    $extended_capability,
                    $viewer);
                if (!$extended_policies) {
                    continue;
                }

                foreach ($extended_policies as $extended_policy) {
                    list($object, $capabilities) = $extended_policy;

                    // Build a description of the capabilities we need to check. This
                    // will be something like `"view"`, or `"edit view"`, or possibly
                    // a longer string with custom capabilities. Later, group the objects
                    // up into groups which need the same capabilities tested.
                    $capabilities = (array)$capabilities;
                    $capabilities = array_fuse($capabilities);
                    ksort($capabilities);
                    $group = implode(' ', $capabilities);

                    $struct = array(
                        'key' => $key,
                        'for' => $extended_capability,
                        'object' => $object,
                        'capabilities' => $capabilities,
                        'group' => $group,
                    );

                    $all_structs[] = $struct;
                }
            }
        }

        // Extract any bare PHIDs from the structs; we need to load these objects.
        // These are objects which are required in order to perform an extended
        // policy check but which the original viewer did not have permission to
        // see (they presumably had other permissions which let them load the
        // object in the first place).
        $all_phids = array();
        foreach ($all_structs as $idx => $struct) {
            $object = $struct['object'];
            if (is_string($object)) {
                $all_phids[$object] = $object;
            }
        }

        // If we have some bare PHIDs, we need to load the corresponding objects.
        if ($all_phids) {
            // We can pull these with the omnipotent user because we're immediately
            // filtering them.
            $ref_objects = (new PhabricatorObjectQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs($all_phids)
                ->execute();
            $ref_objects = mpull($ref_objects, null, 'getPHID');
        } else {
            $ref_objects = array();
        }

        // Group the list of checks by the capabilities we need to check.
        $groups = igroup($all_structs, 'group');
        foreach ($groups as $structs) {
            $head = head($structs);

            // All of the items in each group are checking for the same capabilities.
            $capabilities = $head['capabilities'];

            $key_map = array();
            $objects_in = array();
            foreach ($structs as $struct) {
                $extended_key = $struct['key'];
                if (empty($extended_objects[$extended_key])) {
                    // If this object has already been rejected by an earlier filtering
                    // pass, we don't need to do any tests on it.
                    continue;
                }

                $object = $struct['object'];
                if (is_string($object)) {
                    // This is really a PHID, so look it up.
                    $object_phid = $object;
                    if (empty($ref_objects[$object_phid])) {
                        // We weren't able to load the corresponding object, so just
                        // reject this result outright.

                        $reject = $extended_objects[$extended_key];
                        unset($extended_objects[$extended_key]);

                        // TODO: This could be friendlier.
                        $this->rejectObject($reject, false, '<bad-ref>');
                        continue;
                    }
                    $object = $ref_objects[$object_phid];
                }

                $phid = $object->getPHID();

                $key_map[$phid][] = $extended_key;
                $objects_in[$phid] = $object;
            }

            if ($objects_in) {
                $objects_out = $this->executeExtendedPolicyChecks(
                    $viewer,
                    $capabilities,
                    $objects_in,
                    $key_map);
                $objects_out = mpull($objects_out, null, 'getPHID');
            } else {
                $objects_out = array();
            }

            // If any objects were removed by filtering, we're going to reject all
            // of the original objects which needed them.
            foreach ($objects_in as $phid => $object_in) {
                if (isset($objects_out[$phid])) {
                    // This object survived filtering, so we don't need to throw any
                    // results away.
                    continue;
                }

                foreach ($key_map[$phid] as $extended_key) {
                    if (empty($extended_objects[$extended_key])) {
                        // We've already rejected this object, so we don't need to reject
                        // it again.
                        continue;
                    }

                    $reject = $extended_objects[$extended_key];
                    unset($extended_objects[$extended_key]);

                    // It's possible that we're rejecting this object for multiple
                    // capability/policy failures, but just pick the first one to show
                    // to the user.
                    $first_capability = head($capabilities);
                    $first_policy = $object_in->getPolicy($first_capability);

                    $this->rejectObject($reject, $first_policy, $first_capability);
                }
            }
        }

        return $extended_objects;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $capabilities
     * @param array $objects
     * @param array $key_map
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function executeExtendedPolicyChecks(
        PhabricatorUser $viewer,
        array $capabilities,
        array $objects,
        array $key_map)
    {

        // Do crude cycle detection by seeing if we have a huge stack depth.
        // Although more sophisticated cycle detection is possible in theory,
        // it is difficult with hierarchical objects like subprojects. Many other
        // checks make it difficult to create cycles normally, so just do a
        // simple check here to limit damage.

        static $depth;

        $depth++;

        if ($depth > 32) {
            foreach ($objects as $key => $object) {
                $this->rejectObject($objects[$key], false, '<cycle>');
                unset($objects[$key]);
                continue;
            }
        }

        if (!$objects) {
            return array();
        }

        $caught = null;
        try {
            $result = (new PhabricatorPolicyFilter())
                ->setViewer($viewer)
                ->requireCapabilities($capabilities)
                ->apply($objects);
        } catch (Exception $ex) {
            $caught = $ex;
            $result = null;
        }

        $depth--;

        if ($caught) {
            throw $caught;
        }

        return $result;
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @return bool
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     */
    private function checkCapability(
        PhabricatorPolicyInterface $object,
        $capability)
    {

        $policy = $this->getObjectPolicy($object, $capability);

        if (!$policy) {
            // TODO: Formalize this somehow?
            $policy = PhabricatorPolicies::POLICY_USER;
        }

        if ($policy == PhabricatorPolicies::POLICY_PUBLIC) {
            // If the object is set to "public" but that policy is disabled for this
            // install, restrict the policy to "user".
            if (!PhabricatorEnv::getEnvConfig('policy.allow-public')) {
                $policy = PhabricatorPolicies::POLICY_USER;
            }

            // If the object is set to "public" but the capability is not a public
            // capability, restrict the policy to "user".
            $capobj = PhabricatorPolicyCapability::getCapabilityByKey($capability);
            if (!$capobj || !$capobj->shouldAllowPublicPolicySetting()) {
                $policy = PhabricatorPolicies::POLICY_USER;
            }
        }

        $viewer = $this->viewer;

        if ($viewer->isOmnipotent()) {
            return true;
        }

        if ($object instanceof PhabricatorSpacesInterface) {
            $space_phid = $object->getSpacePHID();
            if (!$this->canViewerSeeObjectsInSpace($viewer, $space_phid)) {
                $this->rejectObjectFromSpace($object, $space_phid);
                return false;
            }
        }

        if ($object->hasAutomaticCapability($capability, $viewer)) {
            return true;
        }

        switch ($policy) {
            case PhabricatorPolicies::POLICY_PUBLIC:
                return true;
            case PhabricatorPolicies::POLICY_USER:
                if ($viewer->getPHID()) {
                    return true;
                } else {
                    $this->rejectObject($object, $policy, $capability);
                }
                break;
            case PhabricatorPolicies::POLICY_ADMIN:
                if ($viewer->getIsAdmin()) {
                    return true;
                } else {
                    if ($viewer->getIsManager()) {
                        $capabilityMap = PhabricatorPolicyCapability::getCapabilityMap();
                        $column = ArrayHelper::getColumn($capabilityMap, function (PhabricatorPolicyCapability $capability) {
                            return $capability->getCapabilityKey();
                        });
                        if(in_array($capability, $column)) {
                            return true;
                        } else {
                            $rbacSettings = $viewer->getRbacSettings();
                            $oldNodes = ArrayHelper::getValue($rbacSettings, 'user.nodes', []);
                            $loadGlobalCapabilities = RbacRole::loadGlobalCapabilities();

                            $dict = array_select_keys($loadGlobalCapabilities, $oldNodes);
                            $list = array_mergev($dict);
                            return in_array($capability, $list);
                        }
                    } else {
                        $this->rejectObject($object, $policy, $capability);
                    }
                }
                break;
            case PhabricatorPolicies::POLICY_NOONE:
                $this->rejectObject($object, $policy, $capability);
                break;
            default:
                if (PhabricatorPolicyQuery::isObjectPolicy($policy)) {
                    if ($this->checkObjectPolicy($policy, $object)) {
                        return true;
                    } else {
                        $this->rejectObject($object, $policy, $capability);
                        break;
                    }
                }

                $type = PhabricatorPHID::phid_get_type($policy);
//                if ($type == PhabricatorProjectProjectPHIDType::TYPECONST) {
//                    if (!empty($this->userProjects[$viewer->getPHID()][$policy])) {
//                        return true;
//                    } else {
//                        $this->rejectObject($object, $policy, $capability);
//                    }
//                } else
                if ($type == PhabricatorPeopleUserPHIDType::TYPECONST) {
                    if ($viewer->getPHID() == $policy) {
                        return true;
                    } else {
                        $this->rejectObject($object, $policy, $capability);
                    }
                } else if ($type == PhabricatorPolicyPHIDTypePolicy::TYPECONST) {
                    if ($this->checkCustomPolicy($policy, $object)) {
                        return true;
                    } else {
                        $this->rejectObject($object, $policy, $capability);
                    }
                } else {
                    // Reject objects with unknown policies.
                    $this->rejectObject($object, false, $capability);
                }
        }

        return false;
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @param $policy
     * @param $capability
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function rejectObject(
        PhabricatorPolicyInterface $object,
        $policy,
        $capability)
    {

        if (!$this->raisePolicyExceptions) {
            return;
        }

        if ($this->viewer->isOmnipotent()) {
            // Never raise policy exceptions for the omnipotent viewer. Although we
            // will never normally issue a policy rejection for the omnipotent
            // viewer, we can end up here when queries blanket reject objects that
            // have failed to load, without distinguishing between nonexistent and
            // nonvisible objects.
            return;
        }

        $capobj = PhabricatorPolicyCapability::getCapabilityByKey($capability);
        $rejection = null;
        if ($capobj) {
            $rejection = $capobj->describeCapabilityRejection();
            $capability_name = $capobj->getCapabilityName();
        } else {
            $capability_name = $capability;
        }

        if (!$rejection) {
            // We couldn't find the capability object, or it doesn't provide a
            // tailored rejection string.
            $rejection = Yii::t("app",
                'You do not have the required capability ("{0}") to do whatever you ' .
                'are trying to do.', [
                    $capability
                ]);
        }

        $more = PhabricatorPolicy::getPolicyExplanation($this->viewer, $policy);
        $more = (array)$more;
        $more = array_filter($more);

        $exceptions = PhabricatorPolicy::getSpecialRules(
            $object,
            $this->viewer,
            $capability,
            true);

        $details = array_filter(array_merge($more, $exceptions));

        $access_denied = $this->renderAccessDenied($object);

        $full_message = Yii::t("app",
            '[{0}] ({1}) {2} // {3]',
            [
                $access_denied,
                $capability_name,
                $rejection,
                implode(' ', $details)
            ]);

        $exception = (new PhabricatorPolicyException($full_message))
            ->setTitle($access_denied)
            ->setObjectPHID($object->getPHID())
            ->setRejection($rejection)
            ->setCapability($capability)
            ->setCapabilityName($capability_name)
            ->setMoreInfo($details);

        throw $exception;
    }

    /**
     * @param array $map
     * @author 陈妙威
     * @throws Exception
     */
    private function loadObjectPolicies(array $map)
    {
        $viewer = $this->viewer;
        $viewer_phid = $viewer->getPHID();

        $rules = PhabricatorPolicyQuery::getObjectPolicyRules(null);

        // Make sure we have clean, empty policy rule objects.
        foreach ($rules as $key => $rule) {
            $rules[$key] = clone $rule;
        }

        $results = array();
        foreach ($map as $key => $object_list) {
            $rule = ArrayHelper::getValue($rules, $key);
            if (!$rule) {
                continue;
            }

            foreach ($object_list as $object_key => $object) {
                if (!$rule->canApplyToObject($object)) {
                    unset($object_list[$object_key]);
                }
            }

            $rule->willApplyRules($viewer, array(), $object_list);
            $results[$key] = $rule;
        }

        $this->objectPolicies[$viewer_phid] = $results;
    }

    /**
     * @param array $map
     * @author 陈妙威
     * @throws \ReflectionException
     * @throws Exception
     * @throws PhutilInvalidStateException
     */
    private function loadCustomPolicies(array $map)
    {
        $viewer = $this->viewer;
        $viewer_phid = $viewer->getPHID();

        /** @var PhabricatorPolicy[] $custom_policies */
        $custom_policies = PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->withPHIDs(array_keys($map))
            ->execute();
        $custom_policies = mpull($custom_policies, null, 'getPHID');

        $classes = array();
        $values = array();
        $objects = array();
        foreach ($custom_policies as $policy_phid => $policy) {
            foreach ($policy->getCustomRuleClasses() as $class) {
                $classes[$class] = $class;
                $values[$class][] = $policy->getCustomRuleValues($class);

                foreach (ArrayHelper::getValue($map, $policy_phid, array()) as $object) {
                    $objects[$class][] = $object;
                }
            }
        }

        foreach ($classes as $class => $ignored) {
            $rule_object = newv($class, array());

            // Filter out any objects which the rule can't apply to.
            $target_objects = ArrayHelper::getValue($objects, $class, array());
            foreach ($target_objects as $key => $target_object) {
                if (!$rule_object->canApplyToObject($target_object)) {
                    unset($target_objects[$key]);
                }
            }

            $rule_object->willApplyRules(
                $viewer,
                array_mergev($values[$class]),
                $target_objects);

            $classes[$class] = $rule_object;
        }

        foreach ($custom_policies as $policy) {
            $policy->attachRuleObjects($classes);
        }

        if (empty($this->customPolicies[$viewer_phid])) {
            $this->customPolicies[$viewer_phid] = array();
        }

        $this->customPolicies[$viewer->getPHID()] += $custom_policies;
    }

    /**
     * @param $policy_phid
     * @param PhabricatorPolicyInterface $object
     * @return bool
     * @author 陈妙威
     */
    private function checkObjectPolicy(
        $policy_phid,
        PhabricatorPolicyInterface $object)
    {
        $viewer = $this->viewer;
        $viewer_phid = $viewer->getPHID();

        /** @var PhabricatorPolicyRule $rule */
        $rule = ArrayHelper::getValue($this->objectPolicies[$viewer_phid], $policy_phid);
        if (!$rule) {
            return false;
        }

        if (!$rule->canApplyToObject($object)) {
            return false;
        }

        return $rule->applyRule($viewer, null, $object);
    }

    /**
     * @param $policy_phid
     * @param PhabricatorPolicyInterface $object
     * @return bool
     * @author 陈妙威
     */
    private function checkCustomPolicy(
        $policy_phid,
        PhabricatorPolicyInterface $object)
    {

        $viewer = $this->viewer;
        $viewer_phid = $viewer->getPHID();

        $policy = ArrayHelper::getValue($this->customPolicies[$viewer_phid], $policy_phid);
        if (!$policy) {
            // Reject, this policy is bogus.
            return false;
        }

        $objects = $policy->getRuleObjects();
        $action = null;
        foreach ($policy->getRules() as $rule) {
            if (!is_array($rule)) {
                // Reject, this policy rule is invalid.
                return false;
            }

            $rule_object = ArrayHelper::getValue($objects, ArrayHelper::getValue($rule, 'rule'));
            if (!$rule_object) {
                // Reject, this policy has a bogus rule.
                return false;
            }

            if (!$rule_object->canApplyToObject($object)) {
                // Reject, this policy rule can't be applied to the given object.
                return false;
            }

            // If the user matches this rule, use this action.
            if ($rule_object->applyRule($viewer, ArrayHelper::getValue($rule, 'value'), $object)) {
                $action = ArrayHelper::getValue($rule, 'action');
                break;
            }
        }

        if ($action === null) {
            $action = $policy->getDefaultAction();
        }

        if ($action === PhabricatorPolicy::ACTION_ALLOW) {
            return true;
        }

        return false;
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @return mixed
     * @author 陈妙威
     */
    private function getObjectPolicy(
        PhabricatorPolicyInterface $object,
        $capability)
    {

        if ($this->forcedPolicy) {
            return $this->forcedPolicy;
        } else {
            $policy = $object->getPolicy($capability);
            return $policy;
        }
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function renderAccessDenied(PhabricatorPolicyInterface $object)
    {
        // NOTE: Not every type of policy object has a real PHID; just load an
        // empty handle if a real PHID isn't available.
        $phid = nonempty($object->getPHID(), PhabricatorPHIDConstants::PHID_VOID);

        $handle = (new PhabricatorHandleQuery())
            ->setViewer($this->viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        $object_name = $handle->getObjectName();

        $is_serious = PhabricatorEnv::getEnvConfig('orangins.serious-business');
        if ($is_serious) {
            $access_denied = Yii::t("app",
                'Access Denied: %s',
                $object_name);
        } else {
            $access_denied = Yii::t("app",
                'You Shall Not Pass: {0}', [
                    $object_name
                ]);
        }

        return $access_denied;
    }


    /**
     * @param PhabricatorUser $viewer
     * @param $space_phid
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function canViewerSeeObjectsInSpace(
        PhabricatorUser $viewer,
        $space_phid)
    {

        $spaces = PhabricatorSpacesNamespaceQuery::getAllSpaces();

        // If there are no spaces, everything exists in an implicit default space
        // with no policy controls. This is the default state.
        if (!$spaces) {
            if ($space_phid !== null) {
                return false;
            } else {
                return true;
            }
        }

        if ($space_phid === null) {
            $space = PhabricatorSpacesNamespaceQuery::getDefaultSpace();
        } else {
            $space = ArrayHelper::getValue($spaces, $space_phid);
        }

        if (!$space) {
            return false;
        }

        // This may be more involved later, but for now being able to see the
        // space is equivalent to being able to see everything in it.
        return self::hasCapability(
            $viewer,
            $space,
            PhabricatorPolicyCapability::CAN_VIEW);
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @param $space_phid
     * @throws Exception
     * @author 陈妙威
     */
    private function rejectObjectFromSpace(
        PhabricatorPolicyInterface $object,
        $space_phid)
    {

        if (!$this->raisePolicyExceptions) {
            return;
        }

        if ($this->viewer->isOmnipotent()) {
            return;
        }

        $access_denied = $this->renderAccessDenied($object);

        $rejection = Yii::t("app",
            'This object is in a space you do not have permission to access.');
        $full_message = Yii::t("app", '[%s] %s', $access_denied, $rejection);

        $exception = (new PhabricatorPolicyException($full_message))
            ->setTitle($access_denied)
            ->setObjectPHID($object->getPHID())
            ->setRejection($rejection)
            ->setCapability(PhabricatorPolicyCapability::CAN_VIEW);

        throw $exception;
    }

    /**
     * @param array $objects
     * @return array
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function applyApplicationChecks(array $objects)
    {
        $viewer = $this->viewer;

        foreach ($objects as $key => $object) {
            // Don't filter handles: users are allowed to see handles from an
            // application they can't see even if they can not see objects from
            // that application. Note that the application policies still apply to
            // the underlying object, so these will be "Restricted Object" handles.

            // If we don't let these through, PhabricatorHandleQuery will completely
            // fail to load results for PHIDs that are part of applications which
            // the viewer can not see, but a fundamental property of handles is that
            // we always load something and they can safely be assumed to load.
            if ($object instanceof PhabricatorObjectHandle) {
                continue;
            }

            $phid = $object->getPHID();
            if (!$phid) {
                continue;
            }

            $application_class = $this->getApplicationForPHID($phid);
            if ($application_class === null) {
                continue;
            }

            $can_see = PhabricatorApplication::isClassInstalledForViewer(
                $application_class,
                $viewer);
            if ($can_see) {
                continue;
            }

            unset($objects[$key]);

            /** @var PhabricatorPolicyInterface $application */
            $application = newv($application_class, array());
            $this->rejectObject(
                $application,
                $application->getPolicy(PhabricatorPolicyCapability::CAN_VIEW),
                PhabricatorPolicyCapability::CAN_VIEW);
        }

        return $objects;
    }

    /**
     * @param $phid
     * @return bool|mixed|null
     * @throws Exception
     * @author 陈妙威
     */
    private function getApplicationForPHID($phid)
    {
        static $class_map = array();

        $phid_type = PhabricatorPHID::phid_get_type($phid);
        if (!isset($class_map[$phid_type])) {
            $type_objects = PhabricatorPHIDType::getTypes(array($phid_type));
            $type_object = ArrayHelper::getValue($type_objects, $phid_type);
            if (!$type_object) {
                $class = false;
            } else {
                $class = $type_object->getPHIDTypeApplicationClass();
            }

            $class_map[$phid_type] = $class;
        }

        $class = $class_map[$phid_type];
        if ($class === false) {
            return null;
        }

        return $class;
    }

}
