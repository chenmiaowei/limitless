<?php

namespace orangins\modules\spaces\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\spaces\application\PhabricatorSpacesApplication;

/**
 * Class PhabricatorSpacesCapabilityDefaultEdit
 * @package orangins\modules\spaces\capability
 * @author 陈妙威
 */
final class PhabricatorSpacesCapabilityDefaultEdit extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'spaces.default.edit';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Default Edit Policy');
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
