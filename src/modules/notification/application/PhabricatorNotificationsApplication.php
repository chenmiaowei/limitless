<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/27
 * Time: 10:13 PM
 */

namespace orangins\modules\notification\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

/**
 * Class OranginsNotificationApplication
 * @package orangins\modules\notification\application
 * @author 陈妙威
 */
class PhabricatorNotificationsApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\notification\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/notification/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'notification';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-bell';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Notifications');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Real-Time Updates and Alerts');
    }

    public function isLaunchable() {
        return false;
    }
}