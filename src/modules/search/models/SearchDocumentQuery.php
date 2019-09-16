<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchDocument]].
 *
 * @see SearchDocument
 */
class SearchDocumentQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return SearchDocument[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return SearchDocument|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
