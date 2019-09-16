<?php

namespace orangins\modules\policy\capability;

use orangins\modules\policy\application\PhabricatorPolicyApplication;

/**
 * Class PhabricatorPolicyCanViewCapability
 * @package orangins\modules\policy\capability
 * @author 陈妙威
 */
final class PhabricatorPolicyCanViewCapability extends PhabricatorPolicyCapability
{
    /**
     *
     */
    const CAPABILITY = self::CAN_VIEW;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can View');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to view this object.');
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
