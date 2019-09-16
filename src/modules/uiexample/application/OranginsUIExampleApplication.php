<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 6:54 PM
 */

namespace orangins\modules\uiexample\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

/**
 * Class OranginsUIExampleApplication
 * @package orangins\modules\uiexample\application
 * @author 陈妙威
 */
class OranginsUIExampleApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\uiexample\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/uiexample/index/query';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'uiexample';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t('app', 'UIExamples');
    }
    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t('app', 'Developer UI Examples');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-magnet';
    }

    public function getApplicationGroup() {
        return self::GROUP_DEVELOPER;
    }
}