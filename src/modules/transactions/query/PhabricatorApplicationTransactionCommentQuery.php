<?php

namespace orangins\modules\transactions\query;

use Exception;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\models\HeraldCondition;
use orangins\modules\herald\models\HeraldRuleapplied;
use yii\db\ActiveRecord;

/**
 * Class PhabricatorApplicationTransactionCommentQuery
 * @package orangins\modules\transactions\query
 * @author 陈妙威
 */
abstract class PhabricatorApplicationTransactionCommentQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $transactionPHIDs;
    /**
     * @var
     */
    private $isDeleted;
    /**
     * @var
     */
    private $hasTransaction;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getTemplate();

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
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $transaction_phids
     * @return $this
     * @author 陈妙威
     */
    public function withTransactionPHIDs(array $transaction_phids)
    {
        $this->transactionPHIDs = $transaction_phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $phids)
    {
        $this->authorPHIDs = $phids;
        return $this;
    }

    /**
     * @param $deleted
     * @return $this
     * @author 陈妙威
     */
    public function withIsDeleted($deleted)
    {
        $this->isDeleted = $deleted;
        return $this;
    }

    /**
     * @param $has_transaction
     * @return $this
     * @author 陈妙威
     */
    public function withHasTransaction($has_transaction)
    {
        $this->hasTransaction = $has_transaction;
        return $this;
    }

    /**
     * @return array|\orangins\lib\db\ActiveRecord[]|HeraldActionRecord[]|HeraldCondition[]|HeraldRuleapplied[]|ActiveRecord[]
     * @throws Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $table = $this->getTemplate();
        $this->from($table);
        $this->buildWhereClause();
        $this->buildOrderClause();
        $this->buildLimitClause();
        return $this->all();
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $this->buildWhereClauseComponents();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildWhereClauseComponents()
    {

        $where = array();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'xcomment.id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'xcomment.phid', $this->phids]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'xcomment.author_phid', $this->authorPHIDs]);
        }

        if ($this->transactionPHIDs !== null) {
            $this->andWhere(['IN', 'xcomment.transaction_phid', $this->transactionPHIDs]);
        }

        if ($this->isDeleted !== null) {
            $this->andWhere([
                'xcomment.is_deleted' => (int)$this->isDeleted
            ]);
        }

        if ($this->hasTransaction !== null) {
            if ($this->hasTransaction) {
                $this->andWhere('xcomment.transaction_phid IS NOT NULL');
            } else {
                $this->andWhere('xcomment.transaction_phid IS NULL');
            }
        }

        return $where;
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        // TODO: Figure out the app via the template?
        return null;
    }

}
