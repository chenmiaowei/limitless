<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/10
 * Time: 11:31 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\userservice\capability\UserServiceBrowseDirectoryCapability;
use orangins\modules\userservice\capability\UserServiceFinanceCapability;

/**
 * Class PhabricatorUserServiceApplication
 * @package orangins\modules\userservice\application
 * @author 陈妙威
 */
class PhabricatorUserServiceApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\userservice\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/userservice/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'userservice';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", '用户数据服务');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return "创建管理用户数据服务";
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-female';
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function isPinnedByDefault(PhabricatorUser $viewer)
    {
        return true;
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            UserServiceBrowseDirectoryCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
            UserServiceFinanceCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_ADMIN;
    }
}