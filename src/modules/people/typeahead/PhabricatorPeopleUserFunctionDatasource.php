<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorPeopleUserFunctionDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorPeopleUserFunctionDatasource
    extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'查看用户');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'请输入用户名');
    }

    /**
     * @return array|\orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource[]
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        $sources = array(
            new PhabricatorViewerDatasource(),
            new PhabricatorPeopleDatasource(),
        );

        return $sources;
    }

}
