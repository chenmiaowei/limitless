<?php

namespace orangins\modules\search\models;

/**
 * This is the ActiveQuery class for [[SearchDocumentrelationship]].
 *
 * @see SearchDocumentrelationship
 */
class SearchDocumentrelationshipQuery extends \orangins\lib\infrastructure\query\PhabricatorQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return SearchDocumentrelationship[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return SearchDocumentrelationship|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
