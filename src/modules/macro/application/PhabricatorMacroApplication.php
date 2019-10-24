<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 7:11 PM
 */

namespace orangins\modules\macro\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\file\capability\FilesDefaultViewCapability;
use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use Yii;

/**
 * Class PhabricatorFilesApplication
 * @package orangins\modules\file\application
 * @author 陈妙威
 */
class PhabricatorMacroApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'macro';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\macro\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/macro/index/query';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Files');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Store and Share Files');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-macro';
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall() {
        return false;
    }
}