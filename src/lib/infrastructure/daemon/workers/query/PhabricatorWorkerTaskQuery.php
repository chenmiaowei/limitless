<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\infrastructure\query\PhabricatorQuery;

/**
 * Class PhabricatorWorkerTaskQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
abstract class PhabricatorWorkerTaskQuery
    extends PhabricatorQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $dateModifiedSince;
    /**
     * @var
     */
    private $dateCreatedBefore;

    /**
     * @var
     */
    private $objectPHIDs;
    /**
     * @var
     */
    private $classNames;
    /**
     * @var
     */
    private $minFailureCount;
    /**
     * @var
     */
    private $maxFailureCount;

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     * @return self
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }


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
     * @param $timestamp
     * @return $this
     * @author 陈妙威
     */
    public function withDateModifiedSince($timestamp)
    {
        $this->dateModifiedSince = $timestamp;
        return $this;
    }

    /**
     * @param $timestamp
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBefore($timestamp)
    {
        $this->dateCreatedBefore = $timestamp;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withObjectPHIDs(array $phids)
    {
        $this->objectPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $names
     * @return $this
     * @author 陈妙威
     */
    public function withClassNames(array $names)
    {
        $this->classNames = $names;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withFailureCountBetween($min, $max)
    {
        $this->minFailureCount = $min;
        $this->maxFailureCount = $max;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $where = array();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->objectPHIDs !== null) {
            $this->andWhere(['IN', 'object_phid', $this->objectPHIDs]);
        }

        if ($this->dateModifiedSince !== null) {
            $this->andWhere('updated_at>:updated_at' ,[
                ":updated_at" =>  $this->dateModifiedSince
            ]);
        }

        if ($this->dateCreatedBefore !== null) {
            $this->andWhere('updated_at<:updated_at' ,[
                ":updated_at" =>  $this->dateCreatedBefore
            ]);
        }

        if ($this->classNames !== null) {
            $this->andWhere(['IN', 'task_class', $this->classNames]);
        }

        if ($this->minFailureCount !== null) {
            $this->andWhere('failure_count>=:failure_count' ,[
                ":failure_count" =>  $this->minFailureCount
            ]);
        }

        if ($this->maxFailureCount !== null) {
            $this->andWhere('failure_count<=:failure_count' ,[
                ":failure_count" =>  $this->maxFailureCount
            ]);
        }
    }

    /**
     * @author 陈妙威
     */
    protected function buildOrderClause()
    {
        // NOTE: The garbage collector executes this query with a date constraint,
        // and the query is inefficient if we don't use the same key for ordering.
        // See T9808 for discussion.

        if ($this->dateCreatedBefore) {
            $this->orderBy("created_at DESC, id DESC");
        } else if ($this->dateModifiedSince) {
            $this->orderBy("updated_at DESC, id DESC");
        } else {
            $this->orderBy("id DESC");
        }
    }
}
