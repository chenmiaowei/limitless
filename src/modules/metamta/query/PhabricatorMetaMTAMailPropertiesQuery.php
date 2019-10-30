<?php

namespace orangins\modules\metamta\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\models\PhabricatorMetaMTAMailProperties;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;

/**
 * Class PhabricatorMetaMTAMailPropertiesQuery
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailPropertiesQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $objectPHIDs;

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
     * @param array $object_phids
     * @return $this
     * @author 陈妙威
     */
    public function withObjectPHIDs(array $object_phids)
    {
        $this->objectPHIDs = $object_phids;
        return $this;
    }

    /**
     * @return PhabricatorMetaMTAMailProperties|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorMetaMTAMailProperties();
    }

    /**
     * @return mixed|PhabricatorPolicyInterface[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
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

        if ($this->objectPHIDs !== null) {
            $where[] = qsprintf(
                $conn,
                'objectPHID IN (%Ls)',
                $this->objectPHIDs);
        }

        return $where;
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

}
