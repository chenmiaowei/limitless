<?php

namespace orangins\modules\meta\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorApplicationsApplication
 * @package orangins\modules\meta\application
 * @author 陈妙威
 */
final class PhabricatorApplicationsApplication extends PhabricatorApplication
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'meta';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\meta\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/meta/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Applications');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        // This application is launchable in the traditional sense, but showing it
        // on the application launch list is confusing.
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Explore More Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-globe';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        return "\xE0\xBC\x84";
    }


//    /**
//     * @param $capability
//     * @return mixed|null|string
//     * @throws \yii\base\Exception
//     * @author 陈妙威
//     */
//    public function getPolicy($capability)
//    {
//        switch ($capability) {
//            case PhabricatorPolicyCapability::CAN_VIEW:
//                return PhabricatorPolicies::POLICY_ADMIN;
//            case PhabricatorPolicyCapability::CAN_EDIT:
//                return PhabricatorPolicies::POLICY_ADMIN;
//            default:
//                $spec = $this->getCustomCapabilitySpecification($capability);
//                return ArrayHelper::getValue($spec, 'default', PhabricatorPolicies::POLICY_USER);
//        }
//    }
}
