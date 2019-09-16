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
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorObjectHandle;

/**
 * Class PhabricatorHandleQuery
 * @package orangins\modules\phid\query
 * @author 陈妙威
 */
class PhabricatorHandleQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    private $objectCapabilities;
    /**
     * @var array
     */
    private $phids = array();

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
     * @param array $capabilities
     * @return $this
     * @author 陈妙威
     */
    public function requireObjectCapabilities(array $capabilities)
    {
        $this->objectCapabilities = $capabilities;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getRequiredObjectCapabilities()
    {
        if ($this->objectCapabilities) {
            return $this->objectCapabilities;
        }
        return $this->getRequiredCapabilities();
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $types = PhabricatorPHIDType::getAllTypes();

        $phids = array_unique($this->phids);
        if (!$phids) {
            return array();
        }

        $object_query = (new PhabricatorObjectQuery())
            ->withPHIDs($phids)
            ->setParentQuery($this)
            ->requireCapabilities($this->getRequiredObjectCapabilities())
            ->setViewer($this->getViewer());

        // We never want the subquery to raise policy exceptions, even if this
        // query is being executed via executeOne(). Policy exceptions are not
        // meaningful or relevant for handles, which load in an "Unknown" or
        // "Restricted" state after encountering a policy violation.
        $object_query->setRaisePolicyExceptions(false);

        $objects = $object_query->execute();
        $filtered = $object_query->getPolicyFilteredPHIDs();

        $groups = array();
        foreach ($phids as $phid) {
            $type = PhabricatorPHID::phid_get_type($phid);
            $groups[$type][] = $phid;
        }

        $results = array();
        foreach ($groups as $type => $phid_group) {
            $handles = array();
            foreach ($phid_group as $key => $phid) {
                if (isset($handles[$phid])) {
                    unset($phid_group[$key]);
                    // The input had a duplicate PHID; just skip it.
                    continue;
                }
                $handles[$phid] = (new PhabricatorObjectHandle())
                    ->setType($type)
                    ->setPHID($phid);
                if (isset($objects[$phid])) {
                    $handles[$phid]->setComplete(true);
                } else if (isset($filtered[$phid])) {
                    $handles[$phid]->setPolicyFiltered(true);
                }
            }

            if (isset($types[$type])) {
                $type_objects = array_select_keys($objects, $phid_group);
                if ($type_objects) {
                    $have_object_phids = array_keys($type_objects);
                    $types[$type]->loadHandles(
                        $this,
                        array_select_keys($handles, $have_object_phids),
                        $type_objects);
                }
            }

            $results += $handles;
        }

        return $results;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return null;
    }
}