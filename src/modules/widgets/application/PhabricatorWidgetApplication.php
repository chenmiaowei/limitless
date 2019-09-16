<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 11:54 AM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\widgets\application;

use orangins\lib\PhabricatorApplication;

/**
 * Class PhabricatorWidgetApplication
 * @package orangins\modules\widgets\application
 * @author 陈妙威
 */
class PhabricatorWidgetApplication extends PhabricatorApplication
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'widgets';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\widgets\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/widgets/index/query';
    }


    /**
     * 获取应用名称
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", "Widgets");
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