<?php

namespace orangins\lib\infrastructure\customfield\datasource;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadProxyDatasource;

/**
 * Class PhabricatorCustomFieldApplicationSearchDatasource
 * @package orangins\lib\infrastructure\customfield\datasource
 * @author 陈妙威
 */
final class PhabricatorCustomFieldApplicationSearchDatasource
    extends PhabricatorTypeaheadProxyDatasource
{

    /**
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        $datasources = parent::getComponentDatasources();

        $datasources[] =
            new PhabricatorCustomFieldApplicationSearchAnyFunctionDatasource();
        $datasources[] =
            new PhabricatorCustomFieldApplicationSearchNoneFunctionDatasource();

        return $datasources;
    }

}
