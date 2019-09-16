<?php

namespace orangins\lib\infrastructure\contentsource;


/**
 * Class PhabricatorUnknownContentSource
 * @package orangins\lib\infrastructure\contentsource
 * @author 陈妙威
 */
final class PhabricatorUnknownContentSource extends PhabricatorContentSource
{
    /**
     *
     */
    const SOURCECONST = 'unknown';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourceName()
    {
        $source = $this->getSource();
        if (strlen($source)) {
            return \Yii::t("app",'Unknown ("{0}")', [
                $source
            ]);
        } else {
            return \Yii::t("app",'Unknown');
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return \Yii::t("app",'Content with no known source.');
    }

}
