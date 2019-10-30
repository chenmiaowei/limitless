<?php

namespace orangins\modules\policy\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\application\PhabricatorPolicyApplication;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\phid\PhabricatorPolicyPHIDTypePolicy;
use Exception;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[PolicyEntities]].
 *
 * @see PhabricatorPolicy
 */
class PhabricatorPolicyQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var PhabricatorPolicyInterface
     */
    private $object;
    /**
     * @var
     */
    private $phids;

    /**
     *
     */
    const OBJECT_POLICY_PREFIX = 'obj.';

    /**
     * @param PhabricatorPolicyInterface $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject(PhabricatorPolicyInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorPolicyInterface $object
     * @return PhabricatorPolicy[]
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public static function loadPolicies(
        PhabricatorUser $viewer,
        PhabricatorPolicyInterface $object)
    {

        $results = array();

        $map = array();
        foreach ($object->getCapabilities() as $capability) {
            $map[$capability] = $object->getPolicy($capability);
        }

        $policies = PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->withPHIDs($map)
            ->execute();

        foreach ($map as $capability => $phid) {
            $results[$capability] = $policies[$phid];
        }

        return $results;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorPolicyInterface $object
     * @param bool $icon
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public static function renderPolicyDescriptions(
        PhabricatorUser $viewer,
        PhabricatorPolicyInterface $object,
        $icon = false)
    {

        $policies = self::loadPolicies($viewer, $object);

        foreach ($policies as $capability => $policy) {
            $policies[$capability] = $policy->renderDescription($icon);
        }

        return $policies;
    }

    /**
     * @return array|null
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        if ($this->object && $this->phids) {
            throw new Exception(
                Yii::t("app",
                    'You can not issue a policy query with both %s and %s.',
                    'setObject()',
                    'setPHIDs()'));
        } else if ($this->object) {
            $phids = $this->loadObjectPolicyPHIDs();
        } else {
            $phids = $this->phids;
        }

        $phids = array_fuse($phids);

        $results = array();

        // First, load global policies.
        foreach (self::getGlobalPolicies() as $phid => $policy) {
            if (isset($phids[$phid])) {
                $results[$phid] = $policy;
                unset($phids[$phid]);
            }
        }

        // Now, load object policies.
        foreach (self::getObjectPolicies($this->object) as $phid => $policy) {
            if (isset($phids[$phid])) {
                $results[$phid] = $policy;
                unset($phids[$phid]);
            }
        }

        // If we still need policies, we're going to have to fetch data. Bucket
        // the remaining policies into rule-based policies and handle-based
        // policies.
        if ($phids) {
            $rule_policies = array();
            $handle_policies = array();
            foreach ($phids as $phid) {
                $phid_type = PhabricatorPHID::phid_get_type($phid);
                if ($phid_type == PhabricatorPolicyPHIDTypePolicy::TYPECONST) {
                    $rule_policies[$phid] = $phid;
                } else {
                    $handle_policies[$phid] = $phid;
                }
            }

            if ($handle_policies) {
                $handles = (new PhabricatorHandleQuery())
                    ->setViewer($this->getViewer())
                    ->withPHIDs($handle_policies)
                    ->execute();
                foreach ($handle_policies as $phid) {
                    $results[$phid] = PhabricatorPolicy::newFromPolicyAndHandle(
                        $phid,
                        $handles[$phid]);
                }
            }

            if ($rule_policies) {
                $rules = PhabricatorPolicy::find()->where(['IN', 'phid', $rule_policies])->all();
                $results += mpull($rules, null, 'getPHID');
            }
        }

        $results = msort($results, 'getSortKey');

        return $results;
    }

    /**
     * @param $policy
     * @return bool
     * @author 陈妙威
     */
    public static function isGlobalPolicy($policy)
    {
        $global_policies = self::getGlobalPolicies();

        if (isset($global_policies[$policy])) {
            return true;
        }

        return false;
    }

    /**
     * @param $policy
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function getGlobalPolicy($policy)
    {
        if (!self::isGlobalPolicy($policy)) {
            throw new Exception(Yii::t("app", "Policy '%s' is not a global policy!", $policy));
        }
        return ArrayHelper::getValue(self::getGlobalPolicies(), $policy);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function getGlobalPolicies()
    {
        static $constants = array(
            PhabricatorPolicies::POLICY_PUBLIC,
            PhabricatorPolicies::POLICY_USER,
            PhabricatorPolicies::POLICY_ADMIN,
            PhabricatorPolicies::POLICY_NOONE,
        );

        $results = array();
        foreach ($constants as $constant) {
            $policyEntities = new PhabricatorPolicy();
            $results[$constant] = $policyEntities
                ->setType(PhabricatorPolicyType::TYPE_GLOBAL)
                ->setPHID($constant)
                ->setName(self::getGlobalPolicyName($constant))
                ->setShortName(self::getGlobalPolicyShortName($constant))
                ->makeEphemeral();
        }

        return $results;
    }

    /**
     * @param $policy
     * @return string
     * @author 陈妙威
     */
    private static function getGlobalPolicyName($policy)
    {
        switch ($policy) {
            case PhabricatorPolicies::POLICY_PUBLIC:
                return Yii::t("app", 'Public (No Login Required)');
            case PhabricatorPolicies::POLICY_USER:
                return Yii::t("app", 'All Users');
            case PhabricatorPolicies::POLICY_ADMIN:
                return Yii::t("app", 'Administrators');
            case PhabricatorPolicies::POLICY_NOONE:
                return Yii::t("app", 'No One');
            default:
                return Yii::t("app", 'Unknown Policy');
        }
    }

    /**
     * @param $policy
     * @return null|string
     * @author 陈妙威
     */
    private static function getGlobalPolicyShortName($policy)
    {
        switch ($policy) {
            case PhabricatorPolicies::POLICY_PUBLIC:
                return Yii::t("app", 'Public');
            default:
                return null;
        }
    }

    /**
     * @return array
     * @throws Exception
     * @throws ReflectionException

     * @author 陈妙威
     */
    private function loadObjectPolicyPHIDs()
    {
        $phids = array();
        $viewer = $this->getViewer();

//        if ($viewer->getPHID()) {
//            $pref_key = PhabricatorPolicyFavoritesSetting::SETTINGKEY;
//
//            $favorite_limit = 10;
//            $default_limit = 5;
//
//            // If possible, show the user's 10 most recently used projects.
//            $favorites = $viewer->getUserSetting($pref_key);
//            if (!is_array($favorites)) {
//                $favorites = array();
//            }
//            $favorite_phids = array_keys($favorites);
//            $favorite_phids = array_slice($favorite_phids, -$favorite_limit);
//
//            if ($favorite_phids) {
//                $projects = (new PhabricatorProjectQuery())
//                    ->setViewer($viewer)
//                    ->withPHIDs($favorite_phids)
//                    ->withIsMilestone(false)
//                    ->setLimit($favorite_limit)
//                    ->execute();
//                $projects = mpull($projects, null, 'getPHID');
//            } else {
//                $projects = array();
//            }
//
//            // If we didn't find enough favorites, add some default projects. These
//            // are just arbitrary projects that the viewer is a member of, but may
//            // be useful on smaller installs and for new users until they can use
//            // the control enough time to establish useful favorites.
////            if (count($projects) < $default_limit) {
//////                $default_projects = (new PhabricatorProjectQuery())
//////                    ->setViewer($viewer)
//////                    ->withMemberPHIDs(array($viewer->getPHID()))
//////                    ->withIsMilestone(false)
//////                    ->withStatuses(
//////                        array(
//////                            PhabricatorProjectStatus::STATUS_ACTIVE,
//////                        ))
//////                    ->setLimit($default_limit)
//////                    ->execute();
//////                $default_projects = mpull($default_projects, null, 'getPHID');
//////                $projects = $projects + $default_projects;
//////                $projects = array_slice($projects, 0, $default_limit);
//////            }
//
//            foreach ($projects as $project) {
//                $phids[] = $project->getPHID();
//            }
//
//            // Include the "current viewer" policy. This improves consistency, but
//            // is also useful for creating private instances of normally-shared object
//            // types, like repositories.
//            $phids[] = $viewer->getPHID();
//        }

        $capabilities = $this->object->getCapabilities();
        foreach ($capabilities as $capability) {
            $policy = $this->object->getPolicy($capability);
            if (!$policy) {
                continue;
            }
            $phids[] = $policy;
        }

        // If this install doesn't have "Public" enabled, don't include it as an
        // option unless the object already has a "Public" policy. In this case we
        // retain the policy but enforce it as though it was "All Users".
        $show_public = PhabricatorEnv::getEnvConfig('policy.allow-public');
        foreach (self::getGlobalPolicies() as $phid => $policy) {
            if ($phid == PhabricatorPolicies::POLICY_PUBLIC) {
                if (!$show_public) {
                    continue;
                }
            }
            $phids[] = $phid;
        }

        foreach (self::getObjectPolicies($this->object) as $phid => $policy) {
            $phids[] = $phid;
        }

        return $phids;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldDisablePolicyFiltering()
    {
        // Policy filtering of policies is currently perilous and not required by
        // the application.
        return true;
    }


    /**
     * @param $identifier
     * @return bool
     * @author 陈妙威
     */
    public static function isSpecialPolicy($identifier)
    {
        if (self::isObjectPolicy($identifier)) {
            return true;
        }

        if (self::isGlobalPolicy($identifier)) {
            return true;
        }

        return false;
    }

    /* -(  Object Policies  )---------------------------------------------------- */


    /**
     * @param $identifier
     * @return bool
     * @author 陈妙威
     */
    public static function isObjectPolicy($identifier)
    {
        $prefix = self::OBJECT_POLICY_PREFIX;
        return !strncmp($identifier, $prefix, strlen($prefix));
    }

    /**
     * @param $identifier
     * @return mixed|null
     * @author 陈妙威
     * @throws Exception
     */
    public static function getObjectPolicy($identifier)
    {
        if (!self::isObjectPolicy($identifier)) {
            return null;
        }

        $policies = self::getObjectPolicies(null);
        return ArrayHelper::getValue($policies, $identifier);
    }

    /**
     * @param $identifier
     * @return mixed|null
     * @throws Exception
     * @author 陈妙威
     */
    public static function getObjectPolicyRule($identifier)
    {
        if (!self::isObjectPolicy($identifier)) {
            return null;
        }

        $rules = self::getObjectPolicyRules(null);
        return ArrayHelper::getValue($rules, $identifier);
    }

    /**
     * @param $object
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getObjectPolicies($object)
    {
        $rule_map = self::getObjectPolicyRules($object);

        $results = array();
        foreach ($rule_map as $key => $rule) {
            $results[$key] = (new PhabricatorPolicy())
                ->setType(PhabricatorPolicyType::TYPE_OBJECT)
                ->setPHID($key)
                ->setIcon($rule->getObjectPolicyIcon())
                ->setName($rule->getObjectPolicyName())
                ->setShortName($rule->getObjectPolicyShortName())
                ->makeEphemeral();
        }

        return $results;
    }

    /**
     * @param $object
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public static function getObjectPolicyRules($object)
    {
        /** @var PhabricatorPolicyRule $rules */
        $rules = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorPolicyRule::class)
            ->execute();

        $results = array();
        foreach ($rules as $rule) {
            $key = $rule->getObjectPolicyKey();
            if (!$key) {
                continue;
            }

            $full_key = $rule->getObjectPolicyFullKey();
            if (isset($results[$full_key])) {
                throw new Exception(
                    Yii::t("app",
                        'Two policy rules (of classes "%s" and "%s") define the same ' .
                        'object policy key ("%s"), but each object policy rule must use ' .
                        'a unique key.',
                        get_class($rule),
                        get_class($results[$full_key]),
                        $key));
            }

            $results[$full_key] = $rule;
        }

        if ($object !== null) {
            foreach ($results as $key => $rule) {
                if (!$rule->canApplyToObject($object)) {
                    unset($results[$key]);
                }
            }
        }

        return $results;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorPolicyInterface $object
     * @param $capability
     * @return null
     * @throws Exception
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public static function getDefaultPolicyForObject(
        PhabricatorUser $viewer,
        PhabricatorPolicyInterface $object,
        $capability)
    {

        $phid = $object->getPHID();
        if (!$phid) {
            return null;
        }

        $type = PhabricatorPHID::phid_get_type($phid);

        $map = self::getDefaultObjectTypePolicyMap();

        if (empty($map[$type][$capability])) {
            return null;
        }

        $policy_phid = $map[$type][$capability];

        return PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->withPHIDs(array($policy_phid))
            ->executeOne();
    }

    /**
     * @return array|null
     * @throws ReflectionException
     * @author 陈妙威
     */
    private static function getDefaultObjectTypePolicyMap()
    {
        static $map;

        if ($map === null) {
            $map = array();

            /** @var PhabricatorApplication[] $apps */
            $apps = PhabricatorApplication::getAllApplicationsWithShortNameKey();
            foreach ($apps as $app) {
                $map += $app->getDefaultObjectTypePolicyMap();
            }
        }

        return $map;
    }


    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorPolicyApplication::class;
    }
}
