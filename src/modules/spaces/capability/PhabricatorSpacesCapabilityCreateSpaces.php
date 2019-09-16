<?php

namespace orangins\modules\spaces\capability;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\spaces\application\PhabricatorSpacesApplication;

/**
 * Class PhabricatorSpacesCapabilityCreateSpaces
 * @package orangins\modules\spaces\capability
 * @author 陈妙威
 */
final class PhabricatorSpacesCapabilityCreateSpaces extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'spaces.create';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return \Yii::t("app", 'Can Create Spaces');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function describeCapabilityRejection()
    {
        return \Yii::t("app", 'You do not have permission to create spaces.');
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
