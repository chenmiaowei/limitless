<?php

namespace orangins\modules\spaces\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\spaces\application\PhabricatorSpacesApplication;
use Yii;

/**
 * Class PhabricatorSpacesCapabilityDefaultView
 * @package orangins\modules\spaces\capability
 * @author 陈妙威
 */
final class PhabricatorSpacesCapabilityDefaultView extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'spaces.default.view';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return Yii::t("app", 'Default View Policy');
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
        return PhabricatorSpacesApplication::className();
    }
}
