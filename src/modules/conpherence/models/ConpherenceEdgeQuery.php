<?php

namespace orangins\modules\conpherence\models;

/**
 * This is the ActiveQuery class for [[ConpherenceIndex]].
 *
 * @see ConpherenceIndex
 */
class ConpherenceEdgeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ConpherenceIndex[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceIndex|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
