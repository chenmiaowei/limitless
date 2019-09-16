<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 1:52 PM
 */

namespace orangins\modules\config\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class OranginsConfigApplication
 * @package orangins\modules\config\application
 */
class PhabricatorConfigApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'config';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\config\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/config/index/index';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-sliders';
    }


    /**
     * @return string
     */
    public function getTitleGlyph()
    {
        return "\xE2\x9C\xA8";
    }

    /**
     * @return string
     */
    public function getApplicationGroup()
    {
        return self::GROUP_ADMIN;
    }

    /**
     * @return bool
     */
    public function canUninstall()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t('app', 'Config');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t('app', 'Configure System');
    }

    /**
     * @param $capability
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::POLICY_ADMIN;
            case PhabricatorPolicyCapability::CAN_EDIT:
                return PhabricatorPolicies::POLICY_ADMIN;
        }
    }
}