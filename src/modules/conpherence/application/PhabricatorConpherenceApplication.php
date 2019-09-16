<?php

namespace orangins\modules\conpherence\application;

use orangins\lib\PhabricatorApplication;

/**
 * Class PhabricatorConpherenceApplication
 * @package orangins\modules\conpherence\application
 * @author 陈妙威
 */
final class PhabricatorConpherenceApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
       return 'conpherence';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\conpherence\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/conpherence/index/query';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Conpherence');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Chat with Others');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-comments';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        return "\xE2\x9C\x86";
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function getQuicksandURIPatternBlacklist()
    {
        return array(
            '/conpherence/.*',
            '/Z\d+',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMailCommandObjects()
    {

        // TODO: Conpherence threads don't currently support any commands directly,
        // so the documentation page we end up generating is empty and funny
        // looking. Add support here once we support "!add", "!leave", "!topic",
        // or whatever else.

        return array();
    }

}
