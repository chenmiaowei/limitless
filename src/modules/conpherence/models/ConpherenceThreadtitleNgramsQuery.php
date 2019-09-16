<?php

namespace orangins\modules\conpherence\models;

/**
 * This is the ActiveQuery class for [[ConpherenceThreadtitleNgrams]].
 *
 * @see ConpherenceThreadtitleNgrams
 */
class ConpherenceThreadtitleNgramsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ConpherenceThreadtitleNgrams[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceThreadtitleNgrams|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
