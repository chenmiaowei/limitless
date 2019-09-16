<?php

namespace orangins\lib\infrastructure\contentsource;

/**
 * Class PhabricatorWebContentSource
 * @package orangins\lib\infrastructure\contentsource
 * @author 陈妙威
 */
final class PhabricatorWebContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'web';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return \Yii::t("app",'Web');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return \Yii::t("app",'Content created from the web UI.');
    }

}
