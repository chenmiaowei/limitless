<?php

namespace orangins\modules\policy\interfaces;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\interfaces\PhabricatorPHIDInterface;

/**
 * Interface PhabricatorPolicyInterface
 * @package orangins\modules\policy\interfaces
 */
interface PhabricatorPolicyInterface extends PhabricatorPHIDInterface
{

    /**
     * @return string[]
     * @author 陈妙威
     */
    public function getCapabilities();

    /**
     * @param $capability
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicy($capability);

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer);

}