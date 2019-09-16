<?php

namespace orangins\modules\people\capability;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PeopleBrowseUserDirectoryCapability
 * @package orangins\modules\people\capability
 * @author 陈妙威
 */
final class PeopleBrowseUserDirectoryCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'people.browse';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Browse User Directory');
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
        return \Yii::t("app", 'You do not have permission to browse the user directory.');
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
