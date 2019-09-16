<?php

namespace orangins\modules\train\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\phid\TransactionPHIDType;

/**
 * Class PhabricatorDashboardApplication
 * @package orangins\modules\dashboard\application
 */
final class PhabricatorTrainApplication extends PhabricatorApplication
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'train';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\train\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/train/index/query';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Trains');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Transactions Pages');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-train';
    }

//    /**
//     * @param PhabricatorUser $viewer
//     * @return bool
//     * @author 陈妙威
//     */
//    public function isPinnedByDefault(PhabricatorUser $viewer)
//    {
//        return true;
//    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLaunchable()
    {
        return true;
    }
}
