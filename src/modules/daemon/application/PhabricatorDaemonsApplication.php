<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/27
 * Time: 10:13 PM
 */

namespace orangins\modules\daemon\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\daemon\workers\OranginsDaemonTestWorker;
use orangins\modules\metamta\workers\PhabricatorMetaMTAWorker;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDaemonsApplication
 * @package orangins\modules\daemon\application
 * @author 陈妙威
 */
class PhabricatorDaemonsApplication extends PhabricatorApplication
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\daemon\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'daemon';
    }


    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-pied-piper-alt';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Daemons');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Manage Phabricator Daemons');
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup() {
        return self::GROUP_ADMIN;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall() {
        return false;
    }

//    /**
//     * @return array
//     * @author 陈妙威
//     */
//    public function getEventListeners() {
//        return array(
//            new PhabricatorDaemonEventListener(),
//        );
//    }


//    public function getRoutes() {
//        return array(
//            '/daemon/' => array(
//                '' => 'PhabricatorDaemonConsoleController',
//                'task/(?P<id>[1-9]\d*)/' => 'PhabricatorWorkerTaskDetailController',
//                'log/' => array(
//                    '' => 'PhabricatorDaemonLogListController',
//                    '(?P<id>[1-9]\d*)/' => 'PhabricatorDaemonLogViewController',
//                ),
//                'bulk/' => array(
//                    '(?:query/(?P<queryKey>[^/]+)/)?' =>
//                        'PhabricatorDaemonBulkJobListController',
//                    'monitor/(?P<id>\d+)/' =>
//                        'PhabricatorDaemonBulkJobMonitorController',
//                    'view/(?P<id>\d+)/' =>
//                        'PhabricatorDaemonBulkJobViewController',
//
//                ),
//            ),
//        );
//    }
}