<?php

namespace orangins\modules\subscriptions\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\subscriptions\event\PhabricatorSubscriptionsUIEventListener;
use Yii;

/**
 * Class PhabricatorSubscriptionsApplication
 * @package orangins\modules\subscriptions\application
 * @author 陈妙威
 */
final class PhabricatorSubscriptionsApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'subscriptions';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\subscriptions\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/subscriptions/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return Yii::t('app', 'Subscriptions');
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function getEventListeners()
    {
        return [
            PhabricatorSubscriptionsUIEventListener::class,
        ];
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable() {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall() {
        return false;
    }

}
