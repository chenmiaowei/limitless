<?php

namespace orangins\modules\conpherence\models;

/**
 * This is the ActiveQuery class for [[ConpherenceThread]].
 *
 * @see ConpherenceThread
 */
class ConpherenceThreadQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ConpherenceThread[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceThread|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
