<?php

namespace orangins\modules\typeahead\typeahead;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorPeopleUserFunctionDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorConduitCompositeDatasource
    extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'接口浏览');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'输入接口的名称');
    }

    /**
     * @return array|\orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource[]
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        $sources = array(
            new PhabricatorConduitDatasource(),
        );
        return $sources;
    }
}
