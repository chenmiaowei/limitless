<?php

namespace orangins\modules\people\capability;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PeopleCreateUsersCapability
 * @package orangins\modules\people\capability
 * @author 陈妙威
 */
final class PeopleCreateUsersCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'people.create.users';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Create (non-bot) Users');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to create users.');
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
