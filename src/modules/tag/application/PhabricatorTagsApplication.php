<?php

namespace orangins\modules\tag\application;

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
final class PhabricatorTagsApplication extends PhabricatorApplication
{

    /**
     * @var string
     */
    public $defaultRoute = "/tag/index/query";

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'tag';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\tag\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/tag/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Tag');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Tag Pages');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-tags';
    }
}
