<?php

namespace orangins\modules\policy\capability;

use orangins\modules\policy\application\PhabricatorPolicyApplication;

/**
 * Class PhabricatorPolicyCanInteractCapability
 * @package orangins\modules\policy\capability
 * @author 陈妙威
 */
final class PhabricatorPolicyCanInteractCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = self::CAN_INTERACT;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Interact');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to interact with this object.');
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
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorPolicyApplication::className();
    }
}
