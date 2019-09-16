<?php

namespace orangins\modules\home\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\home\menuitem\PhabricatorHomeLauncherProfileMenuItem;
use orangins\modules\home\menuitem\PhabricatorHomeProfileMenuItem;
use yii\helpers\Url;

/**
 * Class PhabricatorHomeApplication
 * @package orangins\modules\home\application
 */
final class PhabricatorHomeApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'home';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\home\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/home/index/query';
    }



    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Home');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Command Center');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-home';
    }

    public function isLaunchable() {
        return false;
    }
}
