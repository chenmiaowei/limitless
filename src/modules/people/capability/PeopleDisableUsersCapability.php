<?php

namespace orangins\modules\people\capability;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PeopleDisableUsersCapability
 * @package orangins\modules\people\capability
 * @author 陈妙威
 */
final class PeopleDisableUsersCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'people.disable.users';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Disable Users');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to disable or enable users.');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorPeopleApplication::className();
    }
}
