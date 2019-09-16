<?php

namespace orangins\modules\search\fulltextstorage;

/**
 * Class PhabricatorElasticsearchQueryBuilder
 * @package orangins\modules\search\fulltextstorage
 * @author 陈妙威
 */
class PhabricatorElasticsearchQueryBuilder
{
    /**
     * @var
     */
    protected $name;
    /**
     * @var array
     */
    protected $clauses = array();


    /**
     * @param null $termkey
     * @return array|mixed
     * @author 陈妙威
     */
    public function getClauses($termkey = null)
    {
        $clauses = $this->clauses;
        if ($termkey == null) {
            return $clauses;
        }
        if (isset($clauses[$termkey])) {
            return $clauses[$termkey];
        }
        return array();
    }

    /**
     * @param $clausekey
     * @return int
     * @author 陈妙威
     */
    public function getClauseCount($clausekey)
    {
        if (isset($this->clauses[$clausekey])) {
            return count($this->clauses[$clausekey]);
        } else {
            return 0;
        }
    }

    /**
     * @param $field
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addExistsClause($field)
    {
        return $this->addClause('filter', array(
            'exists' => array(
                'field' => $field,
            ),
        ));
    }

    /**
     * @param $field
     * @param $values
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addTermsClause($field, $values)
    {
        return $this->addClause('filter', array(
            'terms' => array(
                $field => array_values($values),
            ),
        ));
    }

    /**
     * @param $clause
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addMustClause($clause)
    {
        return $this->addClause('must', $clause);
    }

    /**
     * @param $clause
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addFilterClause($clause)
    {
        return $this->addClause('filter', $clause);
    }

    /**
     * @param $clause
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addShouldClause($clause)
    {
        return $this->addClause('should', $clause);
    }

    /**
     * @param $clause
     * @return PhabricatorElasticsearchQueryBuilder
     * @author 陈妙威
     */
    public function addMustNotClause($clause)
    {
        return $this->addClause('must_not', $clause);
    }

    /**
     * @param $clause
     * @param $terms
     * @return $this
     * @author 陈妙威
     */
    public function addClause($clause, $terms)
    {
        $this->clauses[$clause][] = $terms;
        return $this;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function toArray()
    {
        $clauses = $this->getClauses();
        return $clauses;
        $cleaned = array();
        foreach ($clauses as $clause => $subclauses) {
            if (is_array($subclauses) && count($subclauses) == 1) {
                $cleaned[$clause] = array_shift($subclauses);
            } else {
                $cleaned[$clause] = $subclauses;
            }
        }
        return $cleaned;
    }

}
