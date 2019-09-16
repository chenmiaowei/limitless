<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/19
 * Time: 12:50 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\herald\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\file\config\PhabricatorFilesConfigOptions;
use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use yii\helpers\Url;

/**
 * Class PhabricatorHeraldApplication
 * @package orangins\modules\herald\application
 * @author 陈妙威
 */
class PhabricatorHeraldApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\herald\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/herald/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'herald';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Herald');
    }

    public function getShortDescription()
    {
        return \Yii::t("app", "Create Notification Rules");
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-file';
    }
}