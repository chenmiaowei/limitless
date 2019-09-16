<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorPeopleOwnerDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorPeopleOwnerDatasource
    extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Owners');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type a username or function...');
    }

    /**
     * @return array|\orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource[]
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        return array(
            new PhabricatorViewerDatasource(),
            new PhabricatorPeopleNoOwnerDatasource(),
            new PhabricatorPeopleAnyOwnerDatasource(),
            new PhabricatorPeopleDatasource(),
        );
    }

}
