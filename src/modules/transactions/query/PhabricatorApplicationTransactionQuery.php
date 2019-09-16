<?php

namespace orangins\modules\transactions\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilClassMapQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorApplicationTransactionQuery
 * @package orangins\modules\transactions\query
 * @author 陈妙威
 */
abstract class PhabricatorApplicationTransactionQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $objectPHIDs;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $transactionTypes;
    /**
     * @var
     */
    private $withComments;

    /**
     * @var bool
     */
    private $needComments = true;
    /**
     * @var bool
     */
    private $needHandles = true;

    /**
     * @param PhabricatorApplicationTransactionInterface $object
     * @return null
     * @author 陈妙威
     */
    final public static function newQueryForObject(
        PhabricatorApplicationTransactionInterface $object)
    {

        $xaction = $object->getApplicationTransactionTemplate();
        $target_class = get_class($xaction);

        /** @var PhabricatorApplicationTransactionQuery[] $queries */
        $queries = (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->execute();
        foreach ($queries as $query) {
            $query_xaction = $query->getTemplateApplicationTransaction();
            $query_class = get_class($query_xaction);

            if ($query_class === $target_class) {
                return newv(get_class($query), [$query_class]);
            }
        }

        return null;
    }

    /**
     * @return PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    abstract public function getTemplateApplicationTransaction();

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
     * @param array $author_phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $author_phids)
    {
        $this->authorPHIDs = $author_phids;
        return $this;
    }

    /**
     * @param array $transaction_types
     * @return $this
     * @author 陈妙威
     */
    public function withTransactionTypes(array $transaction_types)
    {
        $this->transactionTypes = $transaction_types;
        return $this;
    }

    /**
     * @param $with_comments
     * @return $this
     * @author 陈妙威
     */
    public function withComments($with_comments)
    {
        $this->withComments = $with_comments;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needComments($need)
    {
        $this->needComments = $need;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needHandles($need)
    {
        $this->needHandles = $need;
        return $this;
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $table = $this->getTemplateApplicationTransaction();

        $xactions = $this->loadStandardPage();

        foreach ($xactions as $xaction) {
            $xaction->attachViewer($this->getViewer());
        }

        if ($this->needComments) {
            $comment_phids = array_filter(OranginsUtil::mpull($xactions, 'getCommentPHID'));

            $comments = array();
            if ($comment_phids) {
                $comments =
                    (new PhabricatorApplicationTransactionTemplatedCommentQuery())
                        ->setTemplate($table->getApplicationTransactionCommentObject())
                        ->setViewer($this->getViewer())
                        ->withPHIDs($comment_phids)
                        ->execute();
                $comments = OranginsUtil::mpull($comments, null, 'getPHID');
            }

            foreach ($xactions as $xaction) {
                if ($xaction->getCommentPHID()) {
                    $comment = ArrayHelper::getValue($comments, $xaction->getCommentPHID());
                    if ($comment) {
                        $xaction->attachComment($comment);
                    }
                }
            }
        } else {
            foreach ($xactions as $xaction) {
                $xaction->setCommentNotLoaded(true);
            }
        }

        return $xactions;
    }

    /**
     * @param array $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function willFilterPage(array $xactions)
    {
        $object_phids = array_keys(OranginsUtil::mpull($xactions, null, 'getObjectPHID'));

        $objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($object_phids)
            ->execute();

        foreach ($xactions as $key => $xaction) {
            $object_phid = $xaction->getObjectPHID();
            if (empty($objects[$object_phid])) {
                unset($xactions[$key]);
                continue;
            }
            $xaction->attachObject($objects[$object_phid]);
        }

        // NOTE: We have to do this after loading objects, because the objects
        // may help determine which handles are required (for example, in the case
        // of custom fields).

        if ($this->needHandles) {
            $phids = array();
            foreach ($xactions as $xaction) {
                $phids[$xaction->getPHID()] = $xaction->getRequiredHandlePHIDs();
            }
            $handles = array();
            $merged = OranginsUtil::array_mergev($phids);
            if ($merged) {
                $handles = $this->getViewer()->loadHandles($merged);
                $handles = iterator_to_array($handles);
            }
            foreach ($xactions as $xaction) {
                $xaction->setHandles(
                    OranginsUtil::array_select_keys(
                        $handles,
                        $phids[$xaction->getPHID()]));
            }
        }

        return $xactions;
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->objectPHIDs !== null) {
            $this->andWhere(['IN', 'object_phid', $this->objectPHIDs]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'author_phid', $this->authorPHIDs]);
        }

        if ($this->transactionTypes !== null) {
            $this->andWhere(['IN', 'transaction_type', $this->transactionTypes]);
        }

        if ($this->withComments !== null) {
            if (!$this->withComments) {
                $this->andWhere('c.id IS NULL');
            }
        }
    }

    /**
     * @return array|void
     * @throws PhabricatorEmptyQueryException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildJoinClauseParts()
    {
        parent::buildJoinClauseParts();

        if ($this->withComments !== null) {
            $xaction = $this->getTemplateApplicationTransaction();
            $comment = $xaction->getApplicationTransactionCommentObject();

            // Not every transaction type has comments, so we may be able to
            // implement this constraint trivially.

            if (!$comment) {
                if ($this->withComments) {
                    throw new PhabricatorEmptyQueryException();
                } else {
                    // If we're querying for transactions with no comments and the
                    // transaction type does not support comments, we don't need to
                    // do anything.
                }
            } else {
                if ($this->withComments) {
                    $joins[] = qsprintf(
                        $conn,
                        'JOIN %T c ON x.phid = c.transactionPHID',
                        $comment->getTableName());
                } else {
                    $joins[] = qsprintf(
                        $conn,
                        'LEFT JOIN %T c ON x.phid = c.transactionPHID',
                        $comment->getTableName());
                }
            }
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldGroupQueryResultRows()
    {
        if ($this->withComments !== null) {
            return true;
        }

        return parent::shouldGroupQueryResultRows();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        // TODO: Sort this out?
        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'x';
    }
}
