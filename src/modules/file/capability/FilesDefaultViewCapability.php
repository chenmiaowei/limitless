<?php

namespace orangins\modules\file\capability;

use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use Yii;

/**
 * Class FilesDefaultViewCapability
 * @package orangins\modules\file\capability
 * @author 陈妙威
 */
final class FilesDefaultViewCapability extends PhabricatorPolicyCapability
{

    /**
     *
     */
    const CAPABILITY = 'files.default.view';

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
        return PhabricatorFilesApplication::className();
    }
}
