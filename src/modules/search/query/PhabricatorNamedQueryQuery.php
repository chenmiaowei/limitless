<?php

namespace orangins\modules\search\query;

use Exception;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\models\PhabricatorNamedQuery;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\db\ActiveRecord;

/**
 * Class PhabricatorNamedQueryQuery
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorNamedQueryQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
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
     * @return null|PhabricatorNamedQuery
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorNamedQuery();
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage($this->newResultObject());
    }

    /**
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        $where = parent::buildWhereClauseParts($conn);

        if ($this->ids !== null) {
            $where[] = qsprintf(
                $conn,
                'id IN (%Ld)',
                $this->ids);
        }

        if ($this->engineClassNames !== null) {
            $where[] = qsprintf(
                $conn,
                'engineClassName IN (%Ls)',
                $this->engineClassNames);
        }

        if ($this->userPHIDs !== null) {
            $where[] = qsprintf(
                $conn,
                'userPHID IN (%Ls)',
                $this->userPHIDs);
        }

        if ($this->queryKeys !== null) {
            $where[] = qsprintf(
                $conn,
                'queryKey IN (%Ls)',
                $this->queryKeys);
        }

        return $where;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

}
