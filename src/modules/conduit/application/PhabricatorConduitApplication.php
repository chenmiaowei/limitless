<?php

namespace orangins\modules\conduit\application;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorConduitApplication
 * @package orangins\modules\conduit\application
 * @author 陈妙威
 */
final class PhabricatorConduitApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'conduit';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\conduit\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/conduit/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-tty';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall()
    {
        return false;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        return array(
            array(
                'name' => \Yii::t("app",'Conduit API Overview'),
                'href' => PhabricatorEnv::getDoclink('Conduit API Overview'),
            ),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Conduit');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Developer API');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        return "\xE2\x87\xB5";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_DEVELOPER;
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getApplicationOrder()
    {
        return 0.100;
    }
}
