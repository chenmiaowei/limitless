<?php

namespace orangins\modules\policy\capability;

use orangins\modules\policy\application\PhabricatorPolicyApplication;

/**
 * Class PhabricatorPolicyCanJoinCapability
 * @package orangins\modules\policy\capability
 * @author 陈妙威
 */
final class PhabricatorPolicyCanJoinCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = self::CAN_JOIN;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Join');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to join this object.');
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
