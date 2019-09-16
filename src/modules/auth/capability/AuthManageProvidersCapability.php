<?php

namespace orangins\modules\auth\capability;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class AuthManageProvidersCapability
 * @package orangins\modules\auth\capability
 * @author 陈妙威
 */
final class AuthManageProvidersCapability extends PhabricatorPolicyCapability
{
    /**
     *
     */
    const CAPABILITY = 'auth.manage.providers';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app",'Can Manage Auth Providers');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app",
            'You do not have permission to manage authentication providers.');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorAuthApplication::className();
    }
}
