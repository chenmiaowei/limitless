<?php

namespace orangins\modules\metamta\typeahead;

use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadCompositeDatasource;

/**
 * Class PhabricatorMetaMTAMailableDatasource
 * @package orangins\modules\metamta\typeahead
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailableDatasource extends PhabricatorTypeaheadCompositeDatasource
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app", 'Browse Subscribers');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", 'Type a user, project, package, or mailing list name...');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        return array(
            new PhabricatorPeopleDatasource(),
        );
    }

}
