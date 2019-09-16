<?php

namespace orangins\modules\finance\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\sxbzxr\application\PhabricatorFinanceApplication;

/**
 * Class FinanceDepositCapability
 * @package orangins\modules\finance\capability
 * @author 陈妙威
 */
final class FinanceDepositCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'finance.deposit';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", '为用户充值');
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
        return \Yii::t("app", '您没有充值的权限。');
    }

    /**
     * Return class name of application.
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorFinanceApplication::className();
    }
}
