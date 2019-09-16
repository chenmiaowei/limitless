<?php

namespace orangins\modules\feed\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use yii\helpers\Url;

/**
 * Class OranginsFeedApplication
 * @package orangins\modules\feed\application
 * @author 陈妙威
 */
final class PhabricatorFeedApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'feed';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\feed\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/feed/index/query';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Feed');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Review Recent Activity');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-newspaper-o';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_UTILITIES;
    }
}
