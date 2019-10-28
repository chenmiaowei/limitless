<?php
namespace orangins\modules\herald\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class HeraldManageGlobalRulesCapability
 * @author 陈妙威
 */
final class HeraldManageGlobalRulesCapability
    extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'herald.global';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return pht('Can Manage Global Rules');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return pht('You do not have permission to manage global Herald rules.');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::className();
    }
}
