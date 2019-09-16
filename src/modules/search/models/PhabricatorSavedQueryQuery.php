<?php

namespace orangins\modules\search\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;

/**
 * This is the ActiveQuery class for [[SearchSavedquery]].
 *
 * @see PhabricatorSavedQuery
 */
class PhabricatorSavedQueryQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $engineClassNames;
    /**
     * @var
     */
    private $queryKeys;

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $engine_class_names
     * @return $this
     * @author 陈妙威
     */
    public function withEngineClassNames(array $engine_class_names)
    {
        $this->engineClassNames = $engine_class_names;
        return $this;
    }

    /**
     * @param array $query_keys
     * @return $this
     * @author 陈妙威
     */
    public function withQueryKeys(array $query_keys)
    {
        $this->queryKeys = $query_keys;
        return $this;
    }

    /**
     * @return null
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $activeRecords = $this->loadStandardPage();
        return $activeRecords;
    }

    /**
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $where = array();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->engineClassNames !== null) {
            $this->andWhere(['IN', 'engine_class_name', $this->engineClassNames]);
        }

        if ($this->queryKeys !== null) {
            $this->andWhere(['IN', 'query_key', $this->queryKeys]);
        }

        $this->buildPagingClause();
//        return $this->formatWhereClause($conn, $where);
    }

    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::class;
    }
}
