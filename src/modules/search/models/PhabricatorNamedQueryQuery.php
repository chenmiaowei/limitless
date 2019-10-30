<?php

namespace orangins\modules\search\models;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;

/**
 * This is the ActiveQuery class for [[SearchNamedquery]].
 *
 * @see PhabricatorNamedQuery
 */
class PhabricatorNamedQueryQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $userPHIDs;
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
     * @param array $user_phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $user_phids)
    {
        $this->userPHIDs = $user_phids;
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
     * @return PhabricatorNamedQuery|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorNamedQuery();
    }

    /**
     * @return null
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        $where = parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->engineClassNames !== null) {
            $this->andWhere(['IN', 'engine_class_name', $this->engineClassNames]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }

        if ($this->queryKeys !== null) {
            $this->andWhere(['IN', 'query_key', $this->queryKeys]);
        }
        return $where;
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
