<?php

namespace orangins\modules\policy\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\policy\config\PhabricatorPolicyConfigOptions;
use orangins\modules\policy\editor\PhabricatorPolicyEditEngineExtension;
use orangins\modules\policy\phid\PhabricatorPolicyPHIDTypePolicy;
use orangins\modules\policy\rule\PhabricatorLunarPhasePolicyRule;

/**
 * Class PhabricatorPolicyApplication
 * @package orangins\modules\policy\application
 * @author 陈妙威
 */
final class PhabricatorPolicyApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
       return 'policy';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\policy\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/policy/index/query';
    }


    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-pied-piper-alt';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        return false;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Policy');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Manage Phabricator Daemons');
    }


}
