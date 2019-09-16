<?php

namespace orangins\modules\search\application;

use orangins\lib\PhabricatorApplication;

/**
 * Class PhabricatorSearchApplication
 * @package orangins\modules\search\application
 * @author 陈妙威
 */
final class PhabricatorSearchApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'search';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\search\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/search/index/query';
    }



    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Search');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Full-Text Search');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFlavorText()
    {
        return \Yii::t("app",'Find stuff in big piles.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-search';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        return false;
    }


}
