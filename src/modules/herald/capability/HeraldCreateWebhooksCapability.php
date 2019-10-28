<?php
namespace orangins\modules\herald\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class HeraldCreateWebhooksCapability
 * @author 陈妙威
 */
final class HeraldCreateWebhooksCapability
    extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'herald.webhooks';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return pht('Can Create Webhooks');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return pht('You do not have permission to create webhooks.');
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
