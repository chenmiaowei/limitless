<?php

namespace orangins\modules\userservice\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;

/**
 * Class PeopleBrowseUserDirectoryCapability
 * @package orangins\modules\people\capability
 * @author 陈妙威
 */
final class UserServiceBrowseDirectoryCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'userservice.browse';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", '可以查询用户服务目录');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublicPolicySetting()
    {
        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", '您没有权限批量失信被执行人。');
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
