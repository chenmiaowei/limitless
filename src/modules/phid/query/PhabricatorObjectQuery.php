<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/7
 * Time: 11:29 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\phid\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use PhutilInvalidStateException;

/**
 * Class PhabricatorHandleQuery
 * @package orangins\modules\phid\query
 * @author 陈妙威
 */
class PhabricatorObjectQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var array
     */
    private $phids = array();
    /**
     * @var array
     */
    private $names = array();
    /**
     * @var
     */
    private $types;

    /**
     * @var
     */
    private $namedResults;

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
     * @param array $names
     * @return $this
     * @author 陈妙威
     */
    public function withNames(array $names)
    {
        $this->names = $names;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withTypes(array $types)
    {
        $this->types = $types;
        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        if ($this->namedResults === null) {
            $this->namedResults = array();
        }

        $names = array_unique($this->names);
        $phids = $this->phids;

        // We allow objects to be named by their PHID in addition to their normal
        // name so that, e.g., CLI tools which accept object names can also accept
        // PHIDs and work as users expect.
        $actually_phids = array();
        if ($names) {
            foreach ($names as $key => $name) {
                if (!strncmp($name, 'PHID-', 5)) {
                    $actually_phids[] = $name;
                    $phids[] = $name;
                    unset($names[$key]);
                }
            }
        }

        if ($names) {
            $types = PhabricatorPHIDType::getAllTypes();
            if ($this->types) {
                $types = array_select_keys($types, $this->types);
            }
            $name_results = $this->loadObjectsByName($types, $names);
        } else {
            $name_results = array();
        }

        if ($phids) {
            $phids = array_unique($phids);

            $phid_types = array();
            foreach ($phids as $phid) {
                $phid_type = PhabricatorPHID::phid_get_type($phid);
                $phid_types[$phid_type] = $phid_type;
            }

            $types = PhabricatorPHIDType::getTypes($phid_types);
            if ($this->types) {
                $types = array_select_keys($types, $this->types);
            }

            $phid_results = $this->loadObjectsByPHID($types, $phids);
        } else {
            $phid_results = array();
        }

        foreach ($actually_phids as $phid) {
            if (isset($phid_results[$phid])) {
                $name_results[$phid] = $phid_results[$phid];
            }
        }

        $this->namedResults += $name_results;

        $dict = $phid_results + mpull($name_results, null, 'getPHID');
        return $dict;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getNamedResults()
    {
        if ($this->namedResults === null) {
            throw new PhutilInvalidStateException('execute');
        }
        return $this->namedResults;
    }

    /**
     * @param array $types
     * @param array $names
     * @return array
     * @author 陈妙威
     */
    private function loadObjectsByName(array $types, array $names)
    {
        $groups = array();
        foreach ($names as $name) {
            foreach ($types as $type => $type_impl) {
                if (!$type_impl->canLoadNamedObject($name)) {
                    continue;
                }
                $groups[$type][] = $name;
                break;
            }
        }

        $results = array();
        foreach ($groups as $type => $group) {
            $results += $types[$type]->loadNamedObjects($this, $group);
        }

        return $results;
    }

    /**
     * @param array $types
     * @param array $phids
     * @return array|\dict
     * @throws \Exception
     * @author 陈妙威
     */
    private function loadObjectsByPHID(array $types, array $phids)
    {
        $results = array();

        $groups = array();
        foreach ($phids as $phid) {
            $type = PhabricatorPHID::phid_get_type($phid);
            $groups[$type][] = $phid;
        }

        $in_flight = $this->getPHIDsInFlight();
        foreach ($groups as $type => $group) {
            // We check the workspace for each group, because some groups may trigger
            // other groups to load (for example, transactions load their objects).
            $workspace = $this->getObjectsFromWorkspace($group);

            foreach ($group as $key => $phid) {
                if (isset($workspace[$phid])) {
                    $results[$phid] = $workspace[$phid];
                    unset($group[$key]);
                }
            }

            if (!$group) {
                continue;
            }

            // Don't try to load PHIDs which are already "in flight"; this prevents
            // us from recursing indefinitely if policy checks or edges form a loop.
            // We will decline to load the corresponding objects.
            foreach ($group as $key => $phid) {
                if (isset($in_flight[$phid])) {
                    unset($group[$key]);
                }
            }

            if ($group && isset($types[$type])) {
                $this->putPHIDsInFlight($group);
                /** @var PhabricatorPHIDType $var */
                $var = $types[$type];
                $objects = $var->loadObjects($this, $group);

                $map = mpull($objects, null, 'getPHID');
                $this->putObjectsInWorkspace($map);
                $results += $map;
            }
        }

        return $results;
    }

    /**
     * @param array $filtered
     * @author 陈妙威
     */
    protected function didFilterResults(array $filtered)
    {
        foreach ($this->namedResults as $name => $result) {
            if (isset($filtered[$result->getPHID()])) {
                unset($this->namedResults[$name]);
            }
        }
    }

    /**
     * This query disables policy filtering if the only required capability is
     * the view capability.
     *
     * The view capability is always checked in the subqueries, so we do not need
     * to re-filter results. For any other set of required capabilities, we do.
     */
    protected function shouldDisablePolicyFiltering()
    {
        $view_capability = PhabricatorPolicyCapability::CAN_VIEW;
        if ($this->getRequiredCapabilities() === array($view_capability)) {
            return true;
        }
        return false;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return null;
    }


    /**
     * Select invalid or restricted PHIDs from a list.
     *
     * PHIDs are invalid if their objects do not exist or can not be seen by the
     * viewer. This method is generally used to validate that PHIDs affected by
     * a transaction are valid.
     *
     * @param PhabricatorUser $viewer Viewer.
     * @param array<phid> List of ostensibly valid PHIDs.
     * @return array<phid> List of invalid or restricted PHIDs.
     * @throws PhutilInvalidStateException
     * @throws \ReflectionException
     */
    public static function loadInvalidPHIDsForViewer(
        PhabricatorUser $viewer,
        array $phids)
    {

        if (!$phids) {
            return array();
        }

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs($phids)
            ->execute();
        $objects = mpull($objects, null, 'getPHID');

        $invalid = array();
        foreach ($phids as $phid) {
            if (empty($objects[$phid])) {
                $invalid[] = $phid;
            }
        }

        return $invalid;
    }
}