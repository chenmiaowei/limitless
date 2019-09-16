<?php

namespace orangins\modules\userservice\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;

/**
 * Class PeopleBrowseUserDirectoryCapability
 * @package orangins\modules\people\capability
 * @author 陈妙威
 */
final class UserServiceFinanceCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'userservice.financce';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", '用户服务资金管理');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublicPolicySetting()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", '处理用户充值续费。');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorUserServiceApplication::className();
    }
}
