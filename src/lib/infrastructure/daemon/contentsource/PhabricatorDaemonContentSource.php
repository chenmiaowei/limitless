<?php

namespace orangins\lib\infrastructure\daemon\contentsource;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;

/**
 * Class PhabricatorDaemonContentSource
 * @package orangins\lib\infrastructure\daemon\contentsource
 * @author 陈妙威
 */
final class PhabricatorDaemonContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'daemon';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return \Yii::t("app",'Daemon');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return \Yii::t("app",'Updates from background processing in daemons.');
    }

}
