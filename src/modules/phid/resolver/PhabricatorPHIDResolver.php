<?php

namespace orangins\modules\phid\resolver;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;

/**
 * Resolve a list of identifiers into PHIDs.
 *
 * This class simplifies the process of converting a list of mixed token types
 * (like some PHIDs and some usernames) into a list of just PHIDs.
 */
abstract class PhabricatorPHIDResolver extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param array $phids
     * @return array
     * @author 陈妙威
     */
    final public function resolvePHIDs(array $phids)
    {
        $type_unknown = PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN;

        $names = array();
        foreach ($phids as $key => $phid) {
            if (PhabricatorPHID::phid_get_type($phid) == $type_unknown) {
                $names[$key] = $phid;
            }
        }

        if ($names) {
            $map = $this->getResolutionMap($names);
            foreach ($names as $key => $name) {
                if (isset($map[$name])) {
                    $phids[$key] = $map[$name];
                }
            }
        }

        return $phids;
    }

    /**
     * @param array $names
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getResolutionMap(array $names);

}
