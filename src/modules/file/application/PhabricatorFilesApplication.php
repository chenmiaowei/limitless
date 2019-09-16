<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 7:11 PM
 */

namespace orangins\modules\file\application;

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
class PhabricatorFilesApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'file';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\file\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/file/index/query';
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
        return 'fa-file';
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall() {
        return false;
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            FilesDefaultViewCapability::CAPABILITY => array(
                'caption' => Yii::t('app', 'Default view policy for newly created files.'),
                'template' => PhabricatorFileFilePHIDType::TYPECONST,
                'capability' => PhabricatorPolicyCapability::CAN_VIEW,
            ),
        );
    }
}