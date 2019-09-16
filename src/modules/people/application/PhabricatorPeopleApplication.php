<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 7:13 PM
 */

namespace orangins\modules\people\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\people\capability\PeopleBrowseUserDirectoryCapability;
use orangins\modules\people\capability\PeopleCreateUsersCapability;
use orangins\modules\people\capability\PeopleDisableUsersCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class OranginsPeopleApplication
 * @package orangins\modules\people\application
 * @author 陈妙威
 */
class PhabricatorPeopleApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\people\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/people/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'people';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-users';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'People');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'User Accounts and Profiles');
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities() {
        return array(
            PeopleCreateUsersCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
            PeopleDisableUsersCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
            PeopleBrowseUserDirectoryCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }
}