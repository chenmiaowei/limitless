<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[Stopwords]].
 *
 * @see Stopwords
 */
class StopwordsQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Stopwords[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Stopwords|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
