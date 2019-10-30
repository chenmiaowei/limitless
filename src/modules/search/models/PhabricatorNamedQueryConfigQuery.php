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

/**
 * This is the ActiveQuery class for [[SearchNamedqueryconfig]].
 *
 * @see PhabricatorNamedQueryConfig
 */
class PhabricatorNamedQueryConfigQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

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
    private $scopePHIDs;

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
     * @param array $scope_phids
     * @return $this
     * @author 陈妙威
     */
    public function withScopePHIDs(array $scope_phids)
    {
        $this->scopePHIDs = $scope_phids;
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
     * @return PhabricatorNamedQueryConfig
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorNamedQueryConfig();
    }

    /**
     * @return mixed
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
        parent::buildWhereClauseParts();
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->engineClassNames !== null) {
            $this->andWhere(['IN', 'engine_class_name', $this->engineClassNames]);
        }

        if ($this->scopePHIDs !== null) {
            $this->andWhere(['IN', 'scope_phid', $this->scopePHIDs]);
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSearchApplication::class;
    }
}
