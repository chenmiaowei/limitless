<?php

namespace orangins\modules\conpherence\models;

/**
 * This is the ActiveQuery class for [[ConpherenceTransactionComment]].
 *
 * @see ConpherenceTransactionComment
 */
class ConpherenceTransactionCommentQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ConpherenceTransactionComment[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceTransactionComment|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
