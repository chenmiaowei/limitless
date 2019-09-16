<?php

namespace orangins\modules\guides\application;

use orangins\lib\PhabricatorApplication;

/**
 * Class PhabricatorGuideApplication
 * @package orangins\modules\guides\application
 * @author 陈妙威
 */
final class PhabricatorGuideApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
       return 'guide';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\guide\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/guide/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Guides');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Short Tutorials');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-map-o';
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
