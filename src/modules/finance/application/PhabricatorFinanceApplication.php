<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/10
 * Time: 11:31 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\sxbzxr\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\finance\capability\FinanceDepositCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class PhabricatorSxbzxrApplication
 * @package orangins\modules\finance\application
 * @author 陈妙威
 */
class PhabricatorFinanceApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\finance\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/finance/index/dashboard';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'finance';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", '费用管理');
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-dollar';
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
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            FinanceDepositCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }
}