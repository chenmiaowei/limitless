<?php

namespace orangins\lib\infrastructure\daemon\contentsource;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;

/**
 * Class PhabricatorBulkContentSource
 * @package orangins\lib\infrastructure\daemon\contentsource
 * @author 陈妙威
 */
final class PhabricatorBulkContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'bulk';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return \Yii::t("app",'Bulk Update');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return \Yii::t("app",'Changes made by bulk update.');
    }

}
