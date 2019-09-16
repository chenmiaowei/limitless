<?php

namespace orangins\modules\conpherence\models;

/**
 * This is the ActiveQuery class for [[ConpherenceTransaction]].
 *
 * @see ConpherenceTransaction
 */
class ConpherenceTransactionQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ConpherenceTransaction[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceTransaction|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
