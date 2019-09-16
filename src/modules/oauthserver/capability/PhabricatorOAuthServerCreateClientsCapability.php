<?php

namespace orangins\modules\oauthserver\capability;

use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorOAuthServerCreateClientsCapability
 * @package orangins\modules\oauthserver\capability
 * @author 陈妙威
 */
final class PhabricatorOAuthServerCreateClientsCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'oauthserver.create';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return pht('Can Create OAuth Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return pht('You do not have permission to create OAuth applications.');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorOAuthServerApplication::className();
    }
}
