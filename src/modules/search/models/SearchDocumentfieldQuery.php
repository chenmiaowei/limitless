<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchDocumentfield]].
 *
 * @see SearchDocumentfield
 */
class SearchDocumentfieldQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return SearchDocumentfield[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return SearchDocumentfield|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
