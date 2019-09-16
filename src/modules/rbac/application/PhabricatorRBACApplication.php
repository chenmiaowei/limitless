<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 1:52 PM
 */

namespace orangins\modules\rbac\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class OranginsConfigApplication
 * @package orangins\modules\config\application
 */
class PhabricatorRBACApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'rbac';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\rbac\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/rbac/role/query';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-gear';
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
        return \Yii::t('app', '权限管理');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t('app', '配置权限管理');
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